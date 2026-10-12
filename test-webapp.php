<?php
// ---------------------------------------------------------------
// Database connection (local MariaDB / MySQL)
// ---------------------------------------------------------------
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";

mysqli_report(MYSQLI_REPORT_OFF); // handle errors manually below
$conn = @new mysqli($servername, $username, $password, $dbname);

$successMessage = "";
$errorMessage   = "";

if ($conn->connect_error) {
    $errorMessage = "Database connection failed. Make sure MySQL is running and the database exists.";
}

// ---------------------------------------------------------------
// Handle the form submission
// ---------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$errorMessage) {
    // Capture all four variables from the form
    $firstName   = trim($_POST["FirstName"]   ?? "");
    $lastName    = trim($_POST["LastName"]    ?? "");
    $govPosition = trim($_POST["GovPosition"] ?? "");
    $location    = trim($_POST["Location"]    ?? ""); // captured, but never saved (see flaw below)

    // ===========================================================
    // INTENTIONAL FLAW (for QA testing purposes):
    // The Location value is received from the form above, but it is
    // deliberately LEFT OUT of the INSERT statement and out of
    // bind_param(). Only FirstName, LastName, and GovPosition are
    // saved, so the Location column in the database stays NULL.
    // A tester comparing the form data with the database should
    // catch this missing data.
    // ===========================================================
    $stmt = $conn->prepare(
        "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)"
    );

    if ($stmt) {
        $stmt->bind_param("sss", $firstName, $lastName, $govPosition); // no $location here
        if ($stmt->execute()) {
            $successMessage = "Success! The wish note has been sent.";
        } else {
            $errorMessage = "The wish note could not be saved. Please try again.";
        }
        $stmt->close();
    } else {
        // Fallback error if the query fails to prepare
        $errorMessage = "Error: the query could not be prepared. Check that the table main_corrupt_politicians exists.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kara David's Wish Note</title>
<style>
  :root {
    --blue: #1e73d8;
    --blue-dark: #165cb0;
    --blue-light: #e8f1fc;
    --gray-bg: #eef0f3;
    --text: #1f2933;
    --muted: #5f6b7a;
    --border: #cfd6df;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
    background: var(--gray-bg);
    font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    color: var(--text);
  }
  .card {
    width: 100%;
    max-width: 460px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
    overflow: hidden;
  }
  .card-header {
    background: var(--blue);
    color: #fff;
    padding: 20px 28px;
  }
  .card-header h2 { margin: 0; font-size: 1.4rem; font-weight: 600; }
  .card-body { padding: 24px 28px 28px; }
  .alert {
    padding: 12px 14px;
    border-radius: 6px;
    margin-bottom: 18px;
    font-size: 0.95rem;
  }
  .alert-success { background: #e6f4ea; color: #1e6b34; border: 1px solid #b7dfc2; }
  .alert-error   { background: #fdeaea; color: #a12626; border: 1px solid #f1bcbc; }
  label {
    display: block;
    font-weight: 600;
    font-size: 0.92rem;
    margin-bottom: 6px;
  }
  .field { margin-bottom: 16px; }
  input[type="text"] {
    width: 100%;
    padding: 10px 12px;
    font-size: 1rem;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: #fff;
    color: var(--text);
  }
  input[type="text"]:focus {
    outline: 2px solid var(--blue);
    outline-offset: 0;
    border-color: var(--blue);
  }
  .btn {
    font-size: 1rem;
    font-weight: 600;
    padding: 11px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.15s ease;
  }
  .btn-primary { background: var(--blue); color: #fff; width: 100%; }
  .btn-primary:hover { background: var(--blue-dark); }
  .btn-secondary { background: #e3e7ec; color: var(--text); }
  .btn-secondary:hover { background: #d3d9e0; }
  .btn:focus-visible { outline: 3px solid #9cc4f2; outline-offset: 2px; }

  /* Modal */
  .overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(20, 30, 45, 0.55);
    align-items: center;
    justify-content: center;
    padding: 16px;
    z-index: 100;
  }
  .overlay.open { display: flex; }
  .modal {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    padding: 24px 26px;
  }
  .modal h3 { margin: 0 0 14px; font-size: 1.2rem; color: var(--blue-dark); }
  .summary {
    background: var(--blue-light);
    border-radius: 6px;
    padding: 12px 14px;
    margin-bottom: 20px;
  }
  .summary dl { margin: 0; }
  .summary dt { font-size: 0.8rem; color: var(--muted); margin-top: 8px; }
  .summary dt:first-child { margin-top: 0; }
  .summary dd { margin: 2px 0 0; font-weight: 600; word-break: break-word; }
  .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
</style>
</head>
<body>

<div class="card">
  <div class="card-header">
    <h2>Kara David's Wish Note</h2>
  </div>
  <div class="card-body">

    <?php if ($successMessage): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <form id="wishForm" method="POST" action="">
      <div class="field">
        <label for="FirstName">Target's First Name</label>
        <input type="text" id="FirstName" name="FirstName" maxlength="30" required>
      </div>
      <div class="field">
        <label for="LastName">Target's Last Name</label>
        <input type="text" id="LastName" name="LastName" maxlength="30" required>
      </div>
      <div class="field">
        <label for="GovPosition">Target's Government Position</label>
        <input type="text" id="GovPosition" name="GovPosition" maxlength="20" required>
      </div>
      <div class="field">
        <label for="Location">Target's Location</label>
        <input type="text" id="Location" name="Location" maxlength="40">
      </div>
      <button type="submit" class="btn btn-primary">Send Wish Note</button>
    </form>

  </div>
</div>

<!-- Verification modal -->
<div class="overlay" id="modalOverlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal">
    <h3 id="modalTitle">Verify Target Data</h3>
    <div class="summary">
      <dl>
        <dt>First Name</dt><dd id="mFirstName"></dd>
        <dt>Last Name</dt><dd id="mLastName"></dd>
        <dt>Government Position</dt><dd id="mGovPosition"></dd>
        <dt>Location</dt><dd id="mLocation"></dd>
      </dl>
    </div>
    <div class="modal-actions">
      <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
      <button type="button" class="btn btn-primary" id="confirmBtn" style="width:auto;">Confirm &amp; Send</button>
    </div>
  </div>
</div>

<script>
  var form    = document.getElementById("wishForm");
  var overlay = document.getElementById("modalOverlay");

  // Intercept the submission and show the verification modal
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    document.getElementById("mFirstName").textContent   = form.FirstName.value;
    document.getElementById("mLastName").textContent    = form.LastName.value;
    document.getElementById("mGovPosition").textContent = form.GovPosition.value;
    document.getElementById("mLocation").textContent    = form.Location.value || "(not provided)";
    overlay.classList.add("open");
  });

  // Cancel closes the modal
  document.getElementById("cancelBtn").addEventListener("click", function () {
    overlay.classList.remove("open");
  });

  // Confirm & Send bypasses the interception and submits the POST data
  document.getElementById("confirmBtn").addEventListener("click", function () {
    form.submit();
  });
</script>

</body>
</html>
