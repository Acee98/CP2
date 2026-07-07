/**
 * script.js
 * ----------------------------------------------------------------------
 * Handles client-side switching between the Login and Signup forms on
 * the login_signup.php page, without needing a page reload.
 * ----------------------------------------------------------------------
 */

/**
 * showForm(formId)
 * ------------------------------------------------------------------
 * Shows the form box matching the given element ID and hides all
 * other form boxes.
 *
 * Step-by-step:
 * 1. Select every element with the ".form-box" class (there are two:
 *    #login-form and #signup-form) and remove the "active" class from
 *    each one. Since login_signup.css only displays a .form-box when
 *    it also has the "active" class, this hides both forms first.
 * 2. Find the specific form box that matches the passed-in `formId`
 *    (e.g. "login-form" or "signup-form") and add the "active" class
 *    back to it, making just that one form visible.
 *
 * Called from the inline onclick handlers in login_signup.php:
 * - onclick="showForm('signup-form')" on the login form's "Signup now!" link
 * - onclick="showForm('login-form')" on the signup form's "Login now!" link
 *
 * @param {string} formId  The id of the form-box element to show
 *                          (e.g. "login-form" or "signup-form").
 */
function showForm(formId) {
    // Step 1: hide every form box currently marked as active.
    document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active"));
    // Step 2: show only the requested form box.
    document.getElementById(formId).classList.add("active");
}