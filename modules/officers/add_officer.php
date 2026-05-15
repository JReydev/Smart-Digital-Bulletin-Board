<?php
include_once __DIR__ . '/../include/auth_required.php';

include '../../database/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    $partylist_id = (int) $_POST['partylist_id'];

    // Check if the name already exists in this partylist
    $check_name = mysqli_query($conn, "SELECT id FROM officers WHERE partylist_id = $partylist_id AND name = '$name'");
    if (mysqli_num_rows($check_name) > 0) {
        // Redirect back with an error flag
        header("Location: update_officer.php?partylist_id=$partylist_id&error=name_exists");
        exit;
    }

    // Check if the position is already taken in this partylist based on new FCMS rules
    $position_limits = [
        'President' => 1,
        'Internal Vice President' => 1,
        'External Vice President' => 1,
        'Secretary' => 1,
        'Assistant Secretary' => 999, // No limit specified
        'Treasurer' => 1,
        'Asst. Treasurer' => 999, // No limit specified
        'Auditor' => 1,
        'PRO Internal' => 1,
        'PRO External' => 1,
        '1st Year Representative' => 1,
        '2nd Year Representative' => 1,
        '3rd Year Representative' => 1,
        '4th Year Representative' => 1,
        'Marshall Head' => 2,
        'Senior Multimedia' => 1,
        'Multimedia Team' => 999, // No limit specified
        'Senior Esports' => 1,
        'Esports Team' => 999 // No limit specified
    ];
    
    $limit = $position_limits[$position] ?? 1;
    if ($limit < 999) {
        $check = mysqli_query($conn, "SELECT COUNT(*) as count FROM officers WHERE partylist_id = $partylist_id AND position = '$position'");
        $count = mysqli_fetch_assoc($check)['count'];
        if ($count >= $limit) {
            // Redirect back with an error flag
            header("Location: update_officer.php?partylist_id=$partylist_id&error=position_taken");
            exit;
        }
    }

    // Insert new officer
    mysqli_query($conn, "INSERT INTO officers (name, position, partylist_id) VALUES ('$name', '$position', $partylist_id)");

    header("Location: update_officer.php?partylist_id=$partylist_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Officer</title>
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

        form {
            display: grid;
            gap: 20px;
        }

        label {
            font-weight: 500;
            color: #7B0000;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        input, select {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            color: #333;
            font-size: 1em;
            outline: none;
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            border-color: #7B0000;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }

        select option {
            background: #ffffff;
            color: #333;
        }

        button {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
            border: 1px solid #7B0000;
            padding: 12px 24px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        button:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #7B0000;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .back-link:hover {
            opacity: 0.8;
            transform: translateX(-2px);
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
        }
    </style>
</head>
<body>

<?php include '../include/header.php'; ?>

<div class="header">Add Officer</div>

<div class="container">
    <form method="POST">
        <input type="hidden" name="partylist_id" value="<?= $_GET['partylist_id'] ?>">
        <label>Officer Name</label>
        <input type="text" name="name" required>
        
        <label>Position</label>
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

        <button type="submit">Add Officer</button>
        <br>
        <a class="back-link" href="update_officer.php?partylist_id=<?= $_GET['partylist_id'] ?>">← Back to Officers</a>
    </form>
</div>

</body>
</html>
