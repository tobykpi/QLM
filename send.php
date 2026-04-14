<?php

function redirect_with_alert($message)
{
    echo "<script>
            alert(" . json_encode($message) . ");
            window.location.href='index.html';
          </script>";
    exit;
}

function mime_type_from_extension($extension)
{
    $mime_types = array(
        "jpg" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "png" => "image/png",
        "webp" => "image/webp",
        "gif" => "image/gif",
        "heic" => "image/heic",
        "heif" => "image/heif",
    );

    return isset($mime_types[$extension]) ? $mime_types[$extension] : "";
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit;
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$organization = trim($_POST["organization"] ?? "");
$message = trim($_POST["message"] ?? "");

if ($name === "" || $email === "" || $phone === "" || $message === "") {
    redirect_with_alert("Please complete the required fields before submitting.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_alert("Please enter a valid email address.");
}

$safe_name = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
$safe_email = htmlspecialchars($email, ENT_QUOTES, "UTF-8");
$safe_phone = htmlspecialchars($phone, ENT_QUOTES, "UTF-8");
$safe_organization = htmlspecialchars($organization, ENT_QUOTES, "UTF-8");
$safe_message = htmlspecialchars($message, ENT_QUOTES, "UTF-8");

$attachments = array();
$photo_names = array();
$max_photo_count = 4;
$max_photo_size = 5 * 1024 * 1024;
$allowed_extensions = array("jpg", "jpeg", "png", "webp", "gif", "heic", "heif");
$allowed_mime_types = array(
    "image/jpeg" => true,
    "image/png" => true,
    "image/webp" => true,
    "image/gif" => true,
    "image/heic" => true,
    "image/heif" => true,
    "image/heic-sequence" => true,
    "image/heif-sequence" => true,
);

if (isset($_FILES["photos"]) && is_array($_FILES["photos"]["name"])) {
    $photo_count = 0;
    $finfo = function_exists("finfo_open") ? finfo_open(FILEINFO_MIME_TYPE) : false;

    foreach ($_FILES["photos"]["name"] as $index => $original_name) {
        $upload_error = isset($_FILES["photos"]["error"][$index]) ? (int) $_FILES["photos"]["error"][$index] : UPLOAD_ERR_NO_FILE;
        $original_name = trim((string) $original_name);

        if ($upload_error === UPLOAD_ERR_NO_FILE || $original_name === "") {
            continue;
        }

        $photo_count++;

        if ($photo_count > $max_photo_count) {
            if ($finfo !== false) {
                finfo_close($finfo);
            }

            redirect_with_alert("Please upload no more than 4 photos.");
        }

        if ($upload_error !== UPLOAD_ERR_OK) {
            if ($finfo !== false) {
                finfo_close($finfo);
            }

            redirect_with_alert("There was a problem uploading one of the photos. Please try again.");
        }

        $tmp_name = $_FILES["photos"]["tmp_name"][$index] ?? "";
        $file_size = isset($_FILES["photos"]["size"][$index]) ? (int) $_FILES["photos"]["size"][$index] : 0;
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        if ($tmp_name === "" || !is_uploaded_file($tmp_name)) {
            if ($finfo !== false) {
                finfo_close($finfo);
            }

            redirect_with_alert("One of the uploaded photos could not be processed. Please try again.");
        }

        if ($file_size <= 0 || $file_size > $max_photo_size) {
            if ($finfo !== false) {
                finfo_close($finfo);
            }

            redirect_with_alert("Each photo must be 5MB or smaller.");
        }

        if (!in_array($extension, $allowed_extensions, true)) {
            if ($finfo !== false) {
                finfo_close($finfo);
            }

            redirect_with_alert("Please upload images in JPG, PNG, WEBP, GIF, HEIC, or HEIF format.");
        }

        $mime_type = "";

        if ($finfo !== false) {
            $mime_type = (string) finfo_file($finfo, $tmp_name);
        }

        if ($mime_type === "" || !isset($allowed_mime_types[$mime_type])) {
            $mime_type = mime_type_from_extension($extension);
        }

        if ($mime_type === "") {
            if ($finfo !== false) {
                finfo_close($finfo);
            }

            redirect_with_alert("One of the uploaded files is not a supported image.");
        }

        $safe_file_name = preg_replace("/[^A-Za-z0-9._-]/", "_", basename($original_name));

        if ($safe_file_name === "" || $safe_file_name === null) {
            $safe_file_name = "moving-photo-" . $photo_count . "." . $extension;
        }

        $attachments[] = array(
            "name" => $safe_file_name,
            "type" => $mime_type,
            "tmp_name" => $tmp_name,
        );

        $photo_names[] = $safe_file_name;
    }

    if ($finfo !== false) {
        finfo_close($finfo);
    }
}

// Replace this placeholder when you are ready to send submissions to a live inbox.
$to = "your-email@example.com";
$subject = "New Free Estimate Request - Quick Lift Moving";

$body = "You have received a new free estimate request.\n\n";
$body .= "Full Name: {$safe_name}\n";
$body .= "Email: {$safe_email}\n";
$body .= "Phone Number: {$safe_phone}\n";
$body .= "Organization: " . ($safe_organization !== "" ? $safe_organization : "N/A") . "\n";
$body .= "Photos Included: " . (!empty($photo_names) ? implode(", ", $photo_names) : "None") . "\n\n";
$body .= "Move Details:\n{$safe_message}\n";

$email_configured = $to !== "your-email@example.com";

if (!$email_configured) {
    redirect_with_alert("Your free estimate form is set up. Add your real inbox in send.php when you are ready to enable email delivery.");
}

$headers = "From: noreply@quickliftmoving.com\r\n";
$headers .= "Reply-To: {$safe_email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";

if (!empty($attachments)) {
    $boundary = "==Multipart_Boundary_x" . md5((string) microtime()) . "x";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

    $email_message = "--{$boundary}\r\n";
    $email_message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $email_message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $email_message .= $body . "\r\n";

    foreach ($attachments as $attachment) {
        $file_contents = file_get_contents($attachment["tmp_name"]);

        if ($file_contents === false) {
            redirect_with_alert("One of the uploaded photos could not be attached. Please try again.");
        }

        $email_message .= "--{$boundary}\r\n";
        $email_message .= "Content-Type: " . $attachment["type"] . "; name=\"" . $attachment["name"] . "\"\r\n";
        $email_message .= "Content-Disposition: attachment; filename=\"" . $attachment["name"] . "\"\r\n";
        $email_message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $email_message .= chunk_split(base64_encode($file_contents)) . "\r\n";
    }

    $email_message .= "--{$boundary}--";
} else {
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $email_message = $body;
}

$sent = mail($to, $subject, $email_message, $headers);

if ($sent) {
    redirect_with_alert("Thank you! Your free estimate request has been submitted.");
}

redirect_with_alert("Sorry, there was an issue sending your request. Please try again.");
?>
