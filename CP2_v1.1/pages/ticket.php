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

            <!--
                HEADER CARD
                ------------------------------------------------------
                Matches the top white card in the Figma design:
                just the "Submit New Ticket" title.
            -->
            <div class="ticket-header">
                <h1>Submit New Ticket</h1>
            </div>

            <!--
                FORM CARD
                ------------------------------------------------------
                Second white card holding the intro line plus the
                3 requested sections:
                  1. Category   -> dropdown (Hardware, Software,
                                    Account, Network, Other)
                  2. Subject    -> short input box
                  3. Description -> larger textarea

                Submits to ../logic/ticket_mngmnt.php (mirrors the
                user_mngmnt.php pattern already used for login/signup)
                so a future backend handler can pick up $_POST values:
                category, subject, description.
            -->
            <form class="ticket-form" action="../logic/ticket_mngmnt.php" method="post">
                <p class="ticket-intro">Please provide the details regarding to your issue below:</p>

                <!-- 1. Issue Category -->
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

                <!-- 2. Subject -->
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

                <!-- 3. Description -->
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

                <!--
                    ACTION BUTTONS
                    ----------------------------------------------
                    Cancel is type="button" (not type="submit") so
                    it never triggers form submission/validation —
                    it just navigates back. Currently points back to
                    the user dashboard; update the href if Cancel
                    should go somewhere else once this is wired into
                    the rest of the app.
                -->
                <div class="ticket-actions">
                    <a href="../pages/user.php" class="btn-cancel-ticket">Cancel</a>
                    <button type="submit" class="btn-submit-ticket">Submit Ticket</button>
                </div>
            </form>

        </div>
    </body>
</html>