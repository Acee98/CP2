<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--Linking to Stylesheet-->
    <link rel="stylesheet" href="../css/landing_page.css">
    <title>ZPGC Services</title>
</head>

<body>
    <!-- Top navigation bar: logo on the left, Login button on the right -->
    <nav class="navbar">
        <div class="navdiv">
            <div class="logo">
                <a href="#">
                    <img src="../images/ZPGC.com.png" alt="ZPGC">
                </a>
            </div>
            <ul>
                <!-- Takes the visitor straight to the login form -->
                <button class="btnW"><a href="../pages/login_signup.php">Login</a></button>
            </ul>
        </div>
    </nav>

    <!-- Hero / welcome section shown to first-time / logged-out visitors -->
    <div class="hero">
        <h1 id="header">Welcome to ZPGC Services!</h1>
        <br>
        <h4 id="subhead">Experience seamless technology support that prioritizes your workflow and minimizes downtime.
        </h4>
        <br>
        <!--
            Links to login_signup.php with ?form=signup in the URL.
            login_signup.php reads this via $_GET['form'] and uses it
            to decide which form box (login vs signup) should be shown
            as active when the page loads.
        -->
        <button class="btnR"><a href="../pages/login_signup.php?form=signup">Signup now!</a></button>
    </div>
</body>

</html>