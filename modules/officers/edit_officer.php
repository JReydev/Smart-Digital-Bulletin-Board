<?php
include_once __DIR__ . '/../include/auth_required.php';

include __DIR__ . '/../../database/connect.php';

$officer_id = $_GET['id'] ?? null;
if (!$officer_id) {
    die("Officer ID not specified.");
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("You must be logged in to update officer details.");
}

// Fetch officer details
$result = mysqli_query($conn, "SELECT * FROM officers WHERE id = $officer_id");
$officer = mysqli_fetch_assoc($result);

if (!$officer) {
    die("Officer not found.");
}

// Fetch current photo if exists
$current_photo = null;
if ($officer['multimedia_id']) {
    $photo_query = mysqli_query($conn, "SELECT file_path FROM multimedia_content WHERE id = " . $officer['multimedia_id']);
    if ($photo_result = mysqli_fetch_assoc($photo_query)) {
        $current_photo = $photo_result['file_path'];
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);

    $multimedia_id = $officer['multimedia_id'];

    // Check if user wants to remove existing picture
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
        $multimedia_id = null;
    } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../multimedia/officers/';
        $filename = uniqid() . '_' . basename($_FILES['photo']['name']);
        $targetFile = $uploadDir . $filename;
        $relativePath = 'multimedia/officers/' . $filename;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
            // Insert into multimedia_content
            $insertMedia = mysqli_query($conn, "
                INSERT INTO multimedia_content (file_path, uploaded_by) 
                VALUES ('$relativePath', $user_id)
            ");

            if ($insertMedia) {
                $multimedia_id = mysqli_insert_id($conn);
            } else {
                echo "Failed to insert multimedia content.";
            }
        } else {
            echo "Failed to upload photo.";
        }
    }

    $update = mysqli_query($conn, "
        UPDATE officers 
        SET name = '$name', position = '$position', multimedia_id = " . ($multimedia_id ? $multimedia_id : "NULL") . "
        WHERE id = $officer_id
    ");

    if ($update) {
        header("Location: update_officer.php?partylist_id=" . $officer['partylist_id']);
        exit;
    } else {
        echo "Failed to update officer.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Officer</title>
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

    .form-group {
      margin-bottom: 24px;
    }

    .form-group:last-child {
      margin-bottom: 0;
    }

    label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 500;
      color: #7B0000;
      font-size: 0.95rem;
      margin-bottom: 12px;
    }

    label i {
      color: #7B0000;
      width: 16px;
      text-align: center;
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

    input[type="file"] {
      width: 100%;
      padding: 12px;
      background: #ffffff;
      border: 1px dashed #e0e0e0;
      border-radius: 12px;
      color: #333;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    input[type="file"]:hover {
      border-color: #7B0000;
      background: #f8f9fa;
    }

    .current-photo {
      margin: 15px 0;
      text-align: center;
    }

    .current-photo img {
      max-width: 200px;
      max-height: 200px;
      border-radius: 12px;
      border: 1px solid #e0e0e0;
      padding: 5px;
      background: #f8f9fa;
    }

    .buttons {
      display: flex;
      gap: 12px;
      margin-top: 30px;
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
      text-decoration: none;
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

    @media (max-width: 768px) {
      .header {
        padding: 12px 15px;
        font-size: 1.6em;
      }
      .container {
        width: 95%;
        padding: 20px;
        margin: 15px auto;
      }
      .buttons {
        flex-direction: column;
      }
      .btn {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>

<?php include '../include/header.php'; ?>

<div class="header">
  <span>Edit Officer</span>
</div>

<div class="container">
  <form method="POST" enctype="multipart/form-data">
    <div class="form-group">
      <label>Officer Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($officer['name']) ?>" required>
    </div>

    <div class="form-group">
      <label>Position</label>
      <select name="position" required>
        <option value="">Select Position</option>
        <?php
        $positions = [
          'President', 'Internal Vice President', 'External Vice President', 
          'Secretary', 'Assistant Secretary', 'Treasurer', 'Asst. Treasurer',
          'Auditor', 'PRO Internal', 'PRO External',
          '1st Year Representative', '2nd Year Representative', '3rd Year Representative', '4th Year Representative',
          'Marshall Head', 'Senior Multimedia', 'Multimedia Team', 'Senior Esports', 'Esports Team'
        ];
        foreach ($positions as $pos) {
            $selected = ($pos === $officer['position']) ? 'selected' : '';
            echo "<option value=\"$pos\" $selected>$pos</option>";
        }
        ?>
      </select>
    </div>

    <div class="form-group">
      <label>Officer Photo</label>
      <?php if ($current_photo): ?>
      <div class="current-photo">
        <img src="../../<?= htmlspecialchars($current_photo) ?>" alt="Current Officer Photo">
        <div style="margin-top: 10px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" name="remove_photo" value="1" style="margin: 0;">
            <span style="color: #dc3545; font-size: 0.9rem;">Remove current photo</span>
          </label>
        </div>
      </div>
      <?php endif; ?>
      <input type="file" name="photo" accept="image/*">
      <small style="color: #666; font-size: 0.85rem; margin-top: 5px; display: block;">
        <?php if ($current_photo): ?>
          Select a new photo to replace the current one, or check "Remove current photo" to remove it.
        <?php else: ?>
          Select a photo to upload for this officer.
        <?php endif; ?>
      </small>
    </div>

    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save"></i>
      Update Officer
    </button>
    
    <a href="update_officer.php?partylist_id=<?= $officer['partylist_id'] ?>" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i>
      Back to Officers
    </a>
  </form>
</div>

</body>
</html>
