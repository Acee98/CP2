<?php
require_once '../logic/session_config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'user') {
    header('Location: login_signup.php');
    exit();
}

$ticket_error = $_SESSION['ticket_error'] ?? '';
unset($_SESSION['ticket_error']);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../css/ticket.css">
        <title>ZPGC Services | Ticket Creation</title>
    </head>
    <body>
        <div class="ticket-container">

            <div class="ticket-header">
                <h1>Submit New Ticket</h1>
            </div>

            <form class="ticket-form" action="../logic/ticket_mngmnt.php" method="post">
                <?php if ($ticket_error): ?>
                    <div class="ticket-notice-error"><?= htmlspecialchars($ticket_error) ?></div>
                <?php endif; ?>

                <p class="ticket-intro">Please provide the details regarding to your issue below:</p>

                <div class="ticket-field">
                    <label for="category">Category:</label>
                    <select id="category" name="category" required>
                        <option value="" disabled selected>Select an issue category</option>
                        <option value="hardware">Hardware</option>
                        <option value="software">Software</option>
                        <option value="account">Account</option>
                        <option value="network">Network</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="ticket-field">
                    <label for="subject">Subject:</label>
                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        placeholder="(e.g., Computers monitor not turning on.)"
                        required
                    >
                </div>

                <div class="ticket-field">
                    <label for="description">Description:</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        placeholder="(e.g., The computer in laboratory room doesn't have network connection, only 1 computer is affected.)"
                        required
                    ></textarea>
                </div>

                <div class="ticket-actions">
                    <a href="../pages/user.php" class="btn-cancel-ticket">Cancel</a>
                    <button type="submit" name="submit_ticket" class="btn-submit-ticket">Submit Ticket</button>
                </div>
            </form>

        </div>
    </body>
</html>
