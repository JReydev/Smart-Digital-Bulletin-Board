<?php
include_once __DIR__ . '/../include/auth_required.php';

include __DIR__ . '/../../database/connect.php';

$partylist_id = $_GET['partylist_id'] ?? null;
if (!$partylist_id) {
    die("Partylist not specified.");
}

// Handle partylist name update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_partylist_name'])) {
    $new_name = mysqli_real_escape_string($conn, strtoupper($_POST['partylist_name']));
    
    // Check if name already exists (excluding current partylist)
    $check_name = mysqli_query($conn, "SELECT id FROM partylist WHERE name = '$new_name' AND id != $partylist_id");
    if (mysqli_num_rows($check_name) > 0) {
        $partylist_error = "A partylist with this name already exists.";
    } else {
        // Update partylist name
        $update = mysqli_query($conn, "UPDATE partylist SET name = '$new_name' WHERE id = $partylist_id");
        
        if ($update) {
            $partylist_success = "Partylist name updated successfully.";
        } else {
            $partylist_error = "Failed to update partylist name.";
        }
    }
}

$party = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM partylist WHERE id = $partylist_id"));

// Check if user is admin
$is_admin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;

$officers = mysqli_query($conn, "
  SELECT * FROM officers 
  WHERE partylist_id = $partylist_id AND is_archived = 0
  ORDER BY FIELD(position,
    'President',
    'Internal Vice President',
    'External Vice President',
    'Secretary',
    'Assistant Secretary',
    'Treasurer',
    'Asst. Treasurer',
    'Auditor',
    'PRO Internal',
    'PRO External',
    '1st Year Representative',
    '2nd Year Representative',
    '3rd Year Representative',
    '4th Year Representative',
    'Marshall Head',
    'Senior Multimedia',
    'Multimedia Team',
    'Senior Esports',
    'Esports Team'
  ), id ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Officers - <?= htmlspecialchars($party['name']) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    body {
      background: #ffffff;
      color: #333;
      min-height: 100vh;
      line-height: 1.6;
    }

    .header {
      background: #7B0000;
      color: white;
      padding: 15px 20px;
      font-size: 2em;
      font-weight: 600;
      letter-spacing: -0.5px;
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
      border-bottom: 2px solid rgba(255, 255, 255, 0.1);
      margin: 20px auto;
      display: flex;
      justify-content: center;
      align-items: center;
      width: 90%;
      max-width: 1200px;
      border-radius: 10px;
      position: relative;
      z-index: 1;
    }

    .container {
      width: 90%;
      max-width: 800px;
      background: #ffffff;
      margin: 30px auto;
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid #e0e0e0;
    }

    .add-form {
      background: #ffffff;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 30px;
      border: 1px solid #e0e0e0;
    }

    .form-group {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
      align-items: flex-start;
    }

    .form-control {
      flex: 1;
    }

    input[type="text"],
    select {
      width: 100%;
      padding: 12px 16px;
      background: #ffffff;
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      font-size: 1em;
      color: #333;
      transition: all 0.3s ease;
    }

    input[type="text"]:focus,
    select:focus {
      outline: none;
      border-color: #7B0000;
      box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
    }

    input::placeholder {
      color: #666;
    }

    select {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%237B0000' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: calc(100% - 12px) center;
      padding-right: 35px;
    }

    select option {
      background: #ffffff;
      color: #333;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 24px;
      border-radius: 30px;
      font-size: 0.95rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      text-decoration: none;
      min-height: 44px; /* Touch target size */
      min-width: 44px;
    }

    .btn-primary {
      background: rgba(123, 0, 0, 0.1);
      color: #7B0000;
      border: 1px solid #7B0000;
    }

    .btn-primary:hover {
      background: rgba(123, 0, 0, 0.2);
      transform: translateY(-2px);
    }

    .btn-secondary {
      background: rgba(123, 0, 0, 0.1);
      color: #7B0000;
      border: 1px solid #7B0000;
    }

    .btn-secondary:hover {
      background: rgba(123, 0, 0, 0.2);
      transform: translateY(-2px);
    }

    .table-container {
      background: #ffffff;
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid #e0e0e0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid #e0e0e0;
    }

    th {
      background: #f8f9fa;
      font-weight: 500;
      color: #7B0000;
    }

    td {
      color: #333;
    }

    tr:hover {
      background: #f8f9fa;
    }

    .actions {
      display: flex;
      gap: 8px;
    }

    .error-message {
      background: rgba(123, 0, 0, 0.1);
      color: #7B0000;
      padding: 12px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      border: 1px solid rgba(123, 0, 0, 0.2);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .success-message {
      background: rgba(40, 167, 69, 0.1);
      color: #28a745;
      padding: 12px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      border: 1px solid rgba(40, 167, 69, 0.2);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .partylist-edit-section {
      background: rgba(123, 0, 0, 0.05);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 25px;
      border: 1px solid rgba(123, 0, 0, 0.1);
    }

    .partylist-edit-section h3 {
      color: #7B0000;
      margin-bottom: 15px;
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .partylist-form .form-group {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .partylist-form .form-control {
      flex: 1;
    }

    .back-link {
      display: flex;
      justify-content: center;
      margin-top: 30px;
    }

    @media (max-width: 768px) {
      .header {
        padding: 12px 15px;
        font-size: 1.6em;
      }
      .container {
        width: 95%;
        padding: 15px;
        margin: 15px auto;
      }
      .form-group {
        flex-direction: column;
      }
      .form-control {
        width: 100%;
      }
      .actions {
        flex-direction: column;
      }
      .btn {
        width: 100%;
        justify-content: center;
        min-height: 44px; /* Touch target size */
        padding: 12px 20px;
      }
      .table-container {
        overflow-x: auto;
      }
      table {
        min-width: 600px;
      }
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 1000;
      overflow-y: auto;
      padding: 160px 0;
    }

    .modal.show {
      display: flex;
      align-items: flex-start;
      justify-content: center;
    }

    .modal-content {
      background: #ffffff;
      width: min(400px, 90%);
      margin: 20px auto;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid #e0e0e0;
      transform: scale(0.7);
      opacity: 0;
      transition: all 0.3s ease;
      position: relative;
    }

    .modal.show .modal-content {
      transform: scale(1);
      opacity: 1;
    }

    .modal-header {
      padding: 20px 20px 0;
      color: #7B0000;
      font-size: 1.4em;
      font-weight: 600;
    }

    .modal-header h3 {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 0;
      font-size: 1em;
    }

    .modal-body {
      padding: 20px;
      color: #333;
      text-align: center;
    }

    .modal-footer {
      padding: 20px;
      display: flex;
      justify-content: center;
      gap: 12px;
      border-top: 1px solid #e0e0e0;
    }

    .modal-footer button {
      padding: 10px 18px;
      border-radius: 30px;
      cursor: pointer;
      font-size: 0.9em;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      min-height: 44px; /* Touch target size */
      min-width: 44px;
    }

    .modal-footer .cancel-btn {
      background: rgba(123, 0, 0, 0.1);
      color: #7B0000;
      border: 1px solid #7B0000;
    }

    .modal-footer .cancel-btn:hover {
      background: rgba(123, 0, 0, 0.2);
    }

    .modal-footer .confirm-delete-btn {
      background: rgba(123, 0, 0, 0.1);
      color: #7B0000;
      border: 1px solid #7B0000;
    }

    .modal-footer .confirm-delete-btn:hover {
      background: rgba(123, 0, 0, 0.2);
    }

    .close {
      position: absolute;
      top: 15px;
      right: 20px;
      background: none;
      border: none;
      color: #666;
      font-size: 24px;
      cursor: pointer;
      padding: 0;
      width: auto;
      margin: 0;
    }

    .close:hover {
      color: #333;
    }

    @media (max-width: 768px) {
      .modal-content {
        width: 95%;
        margin: 0 15px;
      }
      
      .partylist-form .form-group {
        flex-direction: column;
        gap: 15px;
      }
      
      .partylist-form .form-control {
        width: 100%;
      }
    }
  </style>
</head>
<body>

<?php include '../include/header.php'; ?>

<div class="header">
  <span>Manage Officers - <?= htmlspecialchars($party['name']) ?></span>
</div>

<div class="container">
  <?php if (isset($_GET['error']) && $_GET['error'] === 'position_taken'): ?>
    <div class="error-message">
      <i class="fas fa-exclamation-circle"></i>
      This position is already filled.
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['error']) && $_GET['error'] === 'name_exists'): ?>
    <div class="error-message">
      <i class="fas fa-exclamation-circle"></i>
      An officer with this name already exists in this partylist.
    </div>
  <?php endif; ?>

  <?php if (isset($partylist_error)): ?>
    <div class="error-message">
      <i class="fas fa-exclamation-circle"></i>
      <?= htmlspecialchars($partylist_error) ?>
    </div>
  <?php endif; ?>

  <?php if (isset($partylist_success)): ?>
    <div class="success-message">
      <i class="fas fa-check-circle"></i>
      <?= htmlspecialchars($partylist_success) ?>
    </div>
  <?php endif; ?>

  <!-- Edit Partylist Name Section -->
  <div class="partylist-edit-section" id="partylist-edit">
    <h3><i class="fas fa-users"></i> Edit Partylist Name</h3>
    <form method="POST" class="partylist-form">
      <input type="hidden" name="update_partylist_name" value="1">
      <div class="form-group">
        <div class="form-control">
          <input type="text" name="partylist_name" value="<?= htmlspecialchars($party['name']) ?>" placeholder="Partylist Name" required>
        </div>
        <button type="submit" class="btn btn-secondary">
          <i class="fas fa-save"></i>
          Update Name
        </button>
      </div>
    </form>
  </div>

  <?php if ($is_admin): ?>
  <div style="margin-bottom: 20px;">
    <a href="archived_officers.php?partylist_id=<?= $partylist_id ?>" class="btn btn-secondary" style="background: rgba(128, 128, 128, 0.1); border-color: rgba(128, 128, 128, 0.2); color: #666;">
      <i class="fas fa-archive"></i>
      View Archived Officers
    </a>
  </div>
  <?php endif; ?>

  <div class="add-form">
    <form action="add_officer.php" method="POST">
      <input type="hidden" name="partylist_id" value="<?= $partylist_id ?>">
      <div class="form-group">
        <div class="form-control">
          <input type="text" name="name" placeholder="Officer Name" required>
        </div>
        <div class="form-control">
          <select name="position" required>
            <option value="">Select Position</option>
            <option value="President">President</option>
            <option value="Internal Vice President">Internal Vice President</option>
            <option value="External Vice President">External Vice President</option>
            <option value="Secretary">Secretary</option>
            <option value="Assistant Secretary">Assistant Secretary</option>
            <option value="Treasurer">Treasurer</option>
            <option value="Asst. Treasurer">Asst. Treasurer</option>
            <option value="Auditor">Auditor</option>
            <option value="PRO Internal">PRO Internal</option>
            <option value="PRO External">PRO External</option>
            <option value="1st Year Representative">1st Year Representative</option>
            <option value="2nd Year Representative">2nd Year Representative</option>
            <option value="3rd Year Representative">3rd Year Representative</option>
            <option value="4th Year Representative">4th Year Representative</option>
            <option value="Marshall Head">Marshall Head</option>
            <option value="Senior Multimedia">Senior Multimedia</option>
            <option value="Multimedia Team">Multimedia Team</option>
            <option value="Senior Esports">Senior Esports</option>
            <option value="Esports Team">Esports Team</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Add Officer
        </button>
      </div>
    </form>
  </div>

  <div class="table-container">
    <table>
      <tr>
        <th>Name</th>
        <th>Position</th>
        <th>Actions</th>
      </tr>
      <?php while ($row = mysqli_fetch_assoc($officers)): ?>
        <tr>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['position']) ?></td>
          <td>
            <div class="actions">
              <a href="edit_officer.php?id=<?= $row['id'] ?>" class="btn btn-secondary">
                <i class="fas fa-edit"></i>
                Edit
              </a>
              <?php if ($is_admin): ?>
              <button class="btn btn-secondary" onclick="showArchiveModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>', '<?= htmlspecialchars(addslashes($row['position'])) ?>')">
                <i class="fas fa-archive"></i>
                Archive
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>

  <div class="back-link">
    <a href="officers.php" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i>
      Back to Officers List
    </a>
  </div>
</div>

<!-- Archive Confirmation Modal -->
<div class="modal" id="archiveModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3><i class="fas fa-archive"></i> Archive Officer</h3>
      <button type="button" class="close" onclick="closeArchiveModal()">&times;</button>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to archive this officer?</p>
      <p style="font-weight: 600; margin-top: 10px;">
        <span id="officerName"></span>
        <span style="color: #666;"> - </span>
        <span id="officerPosition" style="color: #7B0000;"></span>
      </p>
      <p style="color: #666; font-size: 0.9em; margin-top: 10px;">
        <i class="fas fa-info-circle"></i> This officer will be moved to archives and can be restored later.
      </p>
    </div>
    <div class="modal-footer">
      <button type="button" class="cancel-btn" onclick="closeArchiveModal()">
        <i class="fas fa-times"></i> Cancel
      </button>
      <button type="button" class="confirm-delete-btn" onclick="confirmArchive()">
        <i class="fas fa-archive"></i> Archive
      </button>
    </div>
  </div>
</div>

<script>
let archiveId = null;

function showArchiveModal(officerId, officerName, officerPosition) {
  archiveId = officerId;
  document.getElementById('officerName').textContent = officerName;
  document.getElementById('officerPosition').textContent = officerPosition;
  document.getElementById('archiveModal').classList.add('show');
  document.body.style.overflow = 'hidden';
}

function closeArchiveModal() {
  document.getElementById('archiveModal').classList.remove('show');
  document.body.style.overflow = 'auto';
  setTimeout(() => {
    archiveId = null;
    document.getElementById('officerName').textContent = '';
    document.getElementById('officerPosition').textContent = '';
  }, 300);
}

function confirmArchive() {
  if (!archiveId) return;
  
  const submitBtn = document.querySelector('.confirm-delete-btn');
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';
  
  fetch('archive_officer.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    credentials: 'same-origin',
    body: `officer_id=${encodeURIComponent(archiveId)}`
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      return response.text().then(text => {
        throw new Error('Expected JSON but got: ' + text.substring(0, 100));
      });
    }
    return response.json();
  })
  .then(data => {
    if (data.success) {
      closeArchiveModal();
      showSuccess(data.message || 'Officer archived successfully');
      setTimeout(() => location.reload(), 1000);
    } else {
      throw new Error(data.message || 'Failed to archive officer');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showError(error.message || 'Failed to archive officer. Please try again.');
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-archive"></i> Archive';
  });
}

function showSuccess(message) {
  const alert = document.createElement('div');
  alert.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #d4edda; color: #155724; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 10000; display: flex; align-items: center; gap: 10px;';
  alert.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
  document.body.appendChild(alert);
  setTimeout(() => alert.remove(), 3000);
}

function showError(message) {
  const alert = document.createElement('div');
  alert.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #f8d7da; color: #721c24; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 10000; display: flex; align-items: center; gap: 10px;';
  alert.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
  document.body.appendChild(alert);
  setTimeout(() => alert.remove(), 5000);
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('archiveModal');
  if (event.target === modal) {
    closeArchiveModal();
  }
}

// Add keyboard support
document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    closeArchiveModal();
  }
});
</script>

</body>
</html>
