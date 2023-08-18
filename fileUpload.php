<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'db';

$conn = new mysqli($host, $username, $password, $dbname);

function checkUserRole($role) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== $role) {
        die("Access denied");
    }
}

$sql = "SELECT email, file_path FROM upload";
$result = $conn->query($sql);

$uploads = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $uploads[] = $row;
    }
}

$conn->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    checkUserRole('roleA');

    $email = $_POST["email"];
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($_FILES["file"]["name"]);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email";
        exit;
    }

    $fileType = ['image/jpeg', 'image/png'];
    if (!in_array($_FILES["file"]["type"], $fileType)) {
        echo "Only JPEG and PNG files are allowed";
        exit;
    }

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
        $stmt = $conn->prepare("INSERT INTO upload (email, file_path) VALUES (?, ?)");
        $stmt->bind_param('ss', $email, $targetFile);

        if ($stmt->execute()) {
            echo "File successfully uploaded and data stored.";
        } else {
            echo "Error storing data!";
        }

        $stmt->close();
        $conn->close();

    } else {
        echo "Error uploading file";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>File Upload</title>
</head>
<body>
    <?php
    // Display appropriate content based on user role
    if (isset($_SESSION['user'])) {
        if ($_SESSION['user']['role'] === 'roleA') {
            echo '<h1>Upload a File and Enter Your Email</h1>';
            echo '<form action="fileupload.php" method="post" enctype="multipart/form-data">';
            echo '    <label for="email">Email:</label>';
            echo '    <input type="email" id="email" name="email" required>';
            echo '    <br>';
            echo '    <label for="file">Upload a JPEG or PNG file:</label>';
            echo '    <input type="file" id="file" name="file" accept=".jpeg,.png" required>';
            echo '    <br>';
            echo '    <input type="submit" value="Submit">';
            echo '</form>';
        }
    }
    ?>

    <h2>Uploaded Files</h2>
    <table>
        <tr>
            <th>Email</th>
            <th>File Path</th>
        </tr>
        <?php foreach ($uploads as $upload) : ?>
            <tr>
                <td><?php echo $upload['email']; ?></td>
                <td><a href="<?php echo $upload['file_path']; ?>" target="_blank"><?php echo $upload['file_path']; ?></a></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>