<?php
include_once __DIR__ . '/../include/auth_required.php';

include __DIR__ . '/../../database/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partylist_name = mysqli_real_escape_string($conn, strtoupper($_POST['name']));

    // Insert partylist
    mysqli_query($conn, "INSERT INTO partylist (name) VALUES ('$partylist_name')");
    $partylist_id = mysqli_insert_id($conn);

    $positions = [
        'President', 'Internal Vice President', 'External Vice President', 'Secretary', 'Assistant Secretary',
        'Treasurer', 'Asst. Treasurer 1', 'Asst. Treasurer 2', 'Auditor',
        'PRO Internal', 'PRO External', '1st Year Representative', '2nd Year Representative', 
        '3rd Year Representative', '4th Year Representative', 'Marshall Head 1', 'Marshall Head 2',
        'Senior Multimedia', 'Multimedia Team 1', 'Multimedia Team 2', 'Multimedia Team 3', 'Multimedia Team 4',
        'Senior Esports', 'Esports Team 1', 'Esports Team 2', 'Esports Team 3', 'Esports Team 4'
    ];

    foreach ($positions as $position) {
        $input_name = 'officer_' . str_replace(' ', '_', strtolower($position));
        if (!empty($_POST[$input_name])) {
            $officer_name = mysqli_real_escape_string($conn, $_POST[$input_name]);
            $clean_position = str_replace([' 1', ' 2', ' 3', ' 4'], '', $position);
            mysqli_query($conn, "INSERT INTO officers (name, position, partylist_id) VALUES ('$officer_name', '$clean_position', '$partylist_id')");
        }
    }

    header("Location: officers.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Partylist and Officers</title>
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

        form label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            color: #7B0000;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }

        form label i {
            color: #7B0000;
            width: 16px;
            text-align: center;
        }

        form input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1em;
            color: #333;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        form input[type="text"]:focus {
            outline: none;
            border-color: #7B0000;
            box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
        }

        form input[type="text"]::placeholder {
            color: #666;
        }

        .buttons {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            justify-content: center;
        }

        .buttons button,
        .buttons a {
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

        .buttons button[type="submit"] {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
            border: 1px solid #7B0000;
        }

        .buttons button[type="submit"]:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .buttons .back-link {
            background: rgba(123, 0, 0, 0.1);
            color: #7B0000;
            border: 1px solid #7B0000;
        }

        .buttons .back-link:hover {
            background: rgba(123, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .section-title {
            font-size: 1.2em;
            color: #7B0000;
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(123, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
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
            .buttons {
                flex-direction: column;
            }
            .buttons button,
            .buttons a {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<?php include '../include/header.php'; ?>

<div class="header">
    <span>Add Partylist and Officers</span>
</div>

<div class="container">
    <form method="POST">
        <div class="section-title">
            <i class="fas fa-users"></i>
            Partylist Information
        </div>
        <div class="form-group">
            <label>
                <i class="fas fa-flag"></i>
                Partylist Name
            </label>
            <input type="text" name="name" placeholder="e.g. HIRAYA" required>
        </div>

        <div class="section-title">
            <i class="fas fa-user-tie"></i>
            Officer Positions
        </div>

        <?php
        $positions = [
            'President', 'Internal Vice President', 'External Vice President', 'Secretary', 'Assistant Secretary',
            'Treasurer', 'Asst. Treasurer 1', 'Asst. Treasurer 2', 'Auditor',
            'PRO Internal', 'PRO External', '1st Year Representative', '2nd Year Representative', 
            '3rd Year Representative', '4th Year Representative', 'Marshall Head 1', 'Marshall Head 2',
            'Senior Multimedia', 'Multimedia Team 1', 'Multimedia Team 2', 'Multimedia Team 3', 'Multimedia Team 4',
            'Senior Esports', 'Esports Team 1', 'Esports Team 2', 'Esports Team 3', 'Esports Team 4'
        ];

        foreach ($positions as $position) {
            $input_name = 'officer_' . str_replace(' ', '_', strtolower($position));
            echo '<div class="form-group">';
            echo '<label><i class="fas fa-user"></i>' . $position . '</label>';
            echo '<input type="text" name="' . $input_name . '" placeholder="Enter name">';
            echo '</div>';
        }
        ?>

        <div class="buttons">
            <button type="submit">
                <i class="fas fa-plus"></i>
                Add Partylist
            </button>
            <a href="officers.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Officers List
            </a>
        </div>
    </form>
</div>

</body>
</html>
