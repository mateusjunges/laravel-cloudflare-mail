# Usage

The driver is a Laravel mail transport. Once it is configured, every part of Laravel's mail abstraction will route through Cloudflare. You do not need to update mailables, notifications, or controller code.

## Sending via the Mail facade

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderShipped;

Mail::to($user->email)->send(new OrderShipped($order));
```

This sends through whichever mailer is named in `MAIL_MAILER`. If that is `cloudflare`, the message goes through Cloudflare.

## Forcing a specific mailer

To send a single message through Cloudflare regardless of the default, pass the mailer name explicitly:

```php
Mail::mailer('cloudflare')->to($user->email)->send(new OrderShipped($order));
```

This is the recommended pattern during a gradual rollout. You can keep `MAIL_MAILER` on your existing transport and migrate one mailable at a time.

## Notifications

Notifications that use the `mail` channel deliver through the configured mailer with no extra setup:

```php
$user->notify(new InvoicePaid($invoice));
```

If you want a specific notification to go through Cloudflare while leaving the rest on a different driver, set the mailer inside `toMail()`:

```php
public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->mailer('cloudflare')
        ->subject('Your invoice is ready')
        ->line('Thanks for your business.');
}
```

## Mailables

A mailable does not need to know which transport it uses. Define it normally:

```php
class OrderShipped extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your order has shipped',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.shipped');
    }
}
```

The driver passes the rendered HTML and text parts to Cloudflare's API, along with the `from`, `to`, `cc`, `bcc`, `reply_to`, subject, custom headers, and attachments.

## Queued mail

Mailables and notifications that implement `ShouldQueue` work as usual:

```php
Mail::to($user->email)->queue(new OrderShipped($order));
```

When the queued job runs, the worker resolves the `cloudflare` mailer the same way as a synchronous send. If Cloudflare returns a transient failure (such as HTTP 429), Laravel's queue worker treats the job as failed and retries it according to your queue configuration. See [Error handling](error-handling.md) for the details.

## Attachments

Standard Laravel attachments work:

```php
Mail::to($user)->send(
    (new OrderShipped($order))->attachFromStorage('invoices/123.pdf')
);
```

The driver encodes attachments as base64 and includes them in the API payload. Cloudflare enforces a 5 MiB limit on the total message size, including the encoded attachment payload. Exceeding this returns error code 10202 ("email.sending.error.email.too_big"); see [Error handling](error-handling.md).

Inline attachments are not supported. Cloudflare Email Sending has no field for inline parts or content IDs, so a message that embeds an image (for example, via `cid:` references in HTML) is rejected before any HTTP call. The driver throws a `CloudflareTransportException`, which the transport surfaces as a Symfony `TransportException`. If you need imagery in an email, link to a hosted image instead of embedding it, or send it as a regular attachment.

## Custom headers

Any non reserved header you set on the message is forwarded to Cloudflare as part of the `headers` map. This is useful for tenant tags, campaign identifiers, or anything else your downstream tooling consumes.

```php
public function envelope(): Envelope
{
    return new Envelope(
        subject: 'Welcome',
        using: [
            function (Email $message): void {
                $message->getHeaders()->addTextHeader('X-Tenant-Id', (string) auth()->user()->organization_id);
            },
        ],
    );
}
```

Reserved headers (`From`, `To`, `Cc`, `Bcc`, `Reply-To`, `Sender`, `Subject`, `Date`, `Message-ID`, `MIME-Version`, `Content-Type`, `Content-Transfer-Encoding`) are dropped from the custom headers map because Cloudflare derives those from dedicated fields in the payload.

## Testing your own code

Use Laravel's `Mail::fake()` (or `Notification::fake()` for notifications) to assert mail was dispatched in your application's tests. These fakes intercept the message before it reaches any transport, so they work identically whether the configured driver is Cloudflare, log, or anything else.

```php
Mail::fake();

$this->post('/orders', $payload)->assertCreated();

Mail::assertSent(OrderShipped::class, function (OrderShipped $mail) use ($order) {
    return $mail->order->id === $order->id;
});
```

No real API calls are made.

## Next step

Continue with [Error handling](error-handling.md) for details on what can go wrong and how the driver surfaces failures.
