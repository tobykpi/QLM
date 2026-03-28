<?php

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit;
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$organization = trim($_POST["organization"] ?? "");
$message = trim($_POST["message"] ?? "");

if ($name === "" || $email === "" || $message === "") {
    echo "<script>
            alert('Please complete the required fields before submitting.');
            window.location.href='index.html';
          </script>";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>
            alert('Please enter a valid email address.');
            window.location.href='index.html';
          </script>";
    exit;
}

$safe_name = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
$safe_email = htmlspecialchars($email, ENT_QUOTES, "UTF-8");
$safe_organization = htmlspecialchars($organization, ENT_QUOTES, "UTF-8");
$safe_message = htmlspecialchars($message, ENT_QUOTES, "UTF-8");

// Replace this placeholder when you are ready to send submissions to a live inbox.
$to = "your-email@example.com";
$subject = "New Consultation Request - Quick Lift Moving";

$body = "You have received a new consultation request.\n\n";
$body .= "Full Name: {$safe_name}\n";
$body .= "Email: {$safe_email}\n";
$body .= "Organization: " . ($safe_organization !== "" ? $safe_organization : "N/A") . "\n\n";
$body .= "Message:\n{$safe_message}\n";

$headers = "From: noreply@quickliftmoving.com\r\n";
$headers .= "Reply-To: {$safe_email}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$email_configured = $to !== "your-email@example.com";

if (!$email_configured) {
    echo "<script>
            alert('Your consultation form is set up. Add your real inbox in send.php when you are ready to enable email delivery.');
            window.location.href='index.html';
          </script>";
    exit;
}

$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    echo "<script>
            alert('Thank you! Your consultation request has been submitted.');
            window.location.href='index.html';
          </script>";
    exit;
}

echo "<script>
        alert('Sorry, there was an issue sending your request. Please try again.');
        window.location.href='index.html';
      </script>";
exit;
?>
