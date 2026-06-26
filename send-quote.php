<?php
declare(strict_types=1);

// Hope Indoor & Outdoor LLC quote form handler for Hostinger PHP hosting.
// Update this address if the business email changes.
const QUOTE_RECIPIENT = 'ephhope@gmail.com';

function clean_text(string $value, int $maxLength = 500): string
{
    $value = trim(strip_tags($value));
    $value = preg_replace('/[\r\n]+/', ' ', $value) ?? '';
    return mb_substr($value, 0, $maxLength);
}

function clean_multiline(string $value, int $maxLength = 3000): string
{
    $value = trim(strip_tags($value));
    $value = preg_replace("/\r\n?|\n/", "\n", $value) ?? '';
    return mb_substr($value, 0, $maxLength);
}

function fail_request(string $message, int $statusCode = 400): never
{
    http_response_code($statusCode);
    header('Content-Type: text/html; charset=UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Form Error | Hope Indoor & Outdoor LLC</title><style>body{font-family:Arial,sans-serif;background:#fbfaf5;color:#132018;margin:0;display:grid;min-height:100vh;place-items:center;padding:24px}.card{max-width:620px;background:#fff;border-top:6px solid #d7ad42;border-radius:18px;padding:34px;box-shadow:0 18px 55px rgba(7,20,13,.15)}h1{margin-top:0;color:#173b27}a{color:#205137;font-weight:700}</style></head><body><main class="card"><h1>We could not send your request.</h1><p>' . $safeMessage . '</p><p>Please go back and check the form, or call/text <a href="tel:+15049204569">(504) 920-4569</a>.</p><p><a href="/contact.html">Return to the quote form</a></p></main></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /contact.html', true, 303);
    exit;
}

// Honeypot spam check. Bots often complete this hidden field.
if (!empty($_POST['website'] ?? '')) {
    header('Location: /thank-you.html', true, 303);
    exit;
}

$name = clean_text((string)($_POST['name'] ?? ''), 120);
$phone = clean_text((string)($_POST['phone'] ?? ''), 60);
$emailRaw = trim((string)($_POST['email'] ?? ''));
$email = $emailRaw === '' ? '' : filter_var($emailRaw, FILTER_VALIDATE_EMAIL);
$service = clean_text((string)($_POST['service'] ?? ''), 160);
$location = clean_text((string)($_POST['location'] ?? ''), 220);
$message = clean_multiline((string)($_POST['message'] ?? ''), 3000);
$consent = (string)($_POST['consent'] ?? '');

if ($name === '' || $phone === '' || $service === '' || $message === '') {
    fail_request('Please complete your name, phone number, requested service, and project details.');
}

if ($emailRaw !== '' && $email === false) {
    fail_request('Please enter a valid email address or leave the email field blank.');
}

if ($consent !== 'yes') {
    fail_request('Please confirm the contact consent checkbox so the team can respond to your request.');
}

$submittedAt = date('F j, Y \a\t g:i A T');
$subject = 'New Website Quote Request — ' . $service;
$body = "A new quote request was submitted through the Hope Indoor & Outdoor LLC website.\n\n"
      . "Name: {$name}\n"
      . "Phone: {$phone}\n"
      . "Email: " . ($email ?: 'Not provided') . "\n"
      . "Service: {$service}\n"
      . "Property address / area: " . ($location ?: 'Not provided') . "\n\n"
      . "Project details:\n{$message}\n\n"
      . "Contact consent: Yes\n"
      . "Submitted: {$submittedAt}\n";

$host = preg_replace('/[^a-z0-9.-]/i', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
$host = preg_replace('/^www\./i', '', $host) ?: 'localhost';
$fromAddress = $host === 'localhost' ? 'noreply@example.com' : 'website@' . $host;

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: Hope Indoor & Outdoor Website <' . $fromAddress . '>',
    'X-Mailer: PHP/' . PHP_VERSION,
];
if (is_string($email) && $email !== '') {
    $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
}

$sent = @mail(QUOTE_RECIPIENT, $subject, $body, implode("\r\n", $headers));

if (!$sent) {
    fail_request('The website could not send the message through the server. Please call or text (504) 920-4569 so the request is not missed.', 500);
}

header('Location: /thank-you.html', true, 303);
exit;
