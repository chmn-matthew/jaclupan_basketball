<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "jaclupan_basketball";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle file uploads
function uploadFile($file, $target_dir) {
    $target_file = $target_dir . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        $check = getimagesize($file["tmp_name"]);
        if($check !== false) {
            $uploadOk = 1;
        } else {
            echo "File is not an image.";
            $uploadOk = 0;
        }
    }

    // Check if file already exists
    if (file_exists($target_file)) {
        $target_file = $target_dir . time() . '_' . basename($file["name"]);
    }

    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" && $imageFileType != "pdf") {
        echo "Sorry, only JPG, JPEG, PNG, GIF & PDF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
        return false;
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return $target_file;
        } else {
            echo "Sorry, there was an error uploading your file.";
            return false;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $playerName = $_POST['playerName'];
    $teamId = $_POST['teamId'];
    $birthdate = $_POST['birthdate'];
    $position = $_POST['position'];
    $address = $_POST['address'];
    
    // Upload player photo
    $photoPath = "";
    if (!empty($_FILES["playerPhoto"]["name"])) {
        $photoPath = uploadFile($_FILES["playerPhoto"], "uploads/players/");
    }
    
    // Upload ID document
    $idDocumentPath = "";
    if (!empty($_FILES["validId"]["name"])) {
        $idDocumentPath = uploadFile($_FILES["validId"], "uploads/documents/");
    }
    
    // Insert into database
    $sql = "INSERT INTO players (name, team_id, birthdate, position, photo_path, id_document_path, address) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisssss", $playerName, $teamId, $birthdate, $position, $photoPath, $idDocumentPath, $address);
    
    if ($stmt->execute()) {
        echo "<script>alert('Player registered successfully!'); window.location.href = 'index.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
    
    $stmt->close();
}

$conn->close();
?>