
<?php
$sessionAllowWrite = true;
require_once('connection.php');
require_once(__DIR__ . '/../interface/globals.php');
require_once $GLOBALS['srcdir'] . '/ESign/Api.php';

use Esign\Api;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Core\Header;
use OpenEMR\Events\Main\Tabs\RenderEvent;
use OpenEMR\Services\LogoService;
use Symfony\Component\Filesystem\Path;

$logoService = new LogoService();
$menuLogo = $logoService->getLogo('core/menu/primary/');

// Ensure token_main matches so this script can not be run by itself
//  If do not match, then destroy the session and go back to login screen

// print_r($_SESSION);exit;

if ((empty($_GET['token_main'])) && (empty($_GET['set_pid']))) {
    // Below functions are from auth.inc, which is included in globals.php
    authCloseSession();
    authLoginScreen(false);
}

$pid = $_GET['set_pid'];

// SQL query to select all data
$sql = "SELECT * FROM patient_daily_notes where patient_id = $pid";
$result = $conn->query($sql);

$psql = "SELECT * FROM patient_data where id = $pid";
$presult = $conn->query($psql);

if ($presult->num_rows > 0) {
    // output data of each row
   $patientData = $presult->fetch_assoc();
}

if(isset($patientData['DOB']) && $patientData['DOB'] != ""){

    $patient_dob = "1996-08-15";
    $patient_age = calculateAge($patient_dob);
}else{
    $patient_age = "N/A";
}


function calculateAge($dob) {
    $dob = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($dob);
    
    return $age->y;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT Daily Treatment SOAP Notes | Motion Sync EMR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/all.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/logo.jpg">
</head>

<body>
    <div class="container">
        <header>
            <img class="logo" src="images/logo.jpg" alt="logo">
            <h1 class="intake-title" class="text-center">Physical Therapy</h1>
            <p id="description" class="text-center description">Motion Sync EMR</p>
            <a class="btn btn-primary" href="addnote.php?token_main=<?php echo $_GET['token_main']."&term=".$patientData['id'] ?>"><i class="fa-solid fa-circle-plus"></i> Add New Patient Progress Note</a>
        </header>

        <h2 class="intake-title">Physical Therapy Daily Treatment SOAP Notes (<?php echo $patientData['title']. ' '. $patientData['fname'] .' '. $patientData['lname']    ?> )</h2>
    <table class="table table-striped intake-forms-table text-center">
        <thead>
            <tr>
                <th>No. of Notes</th>
                <th>Date of Service</th>
                <th>Date of Treatment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
             $couter = 1;
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $couter . "</td>";
                    echo "<td>" . $row['date_of_service']  . "</td>";
                    echo "<td>" . $row['treatment_date'] . "</td>";
                    echo "<td> <a href='viewnote.php?token_main=".$_GET['token_main']."&term=".$row['id']."&pid=".$patientData['id']."' class='btn btn-info'><i class='fa-solid fa-eye'></i> View</a> <a href='updatenote.php?token_main=".$_GET['token_main']."&term=".$row['id']."&pid=".$patientData['id']."' class='btn btn-primary'><i class='fa-solid fa-pen-to-square'></i> Update</a></td>";
                    //echo "<td> <a href='javascript:void(0);' class='btn btn-info'><i class='fa-solid fa-eye'></i> View</a> <a href='javascript:void(0);' class='btn btn-primary'><i class='fa-solid fa-pen-to-square'></i> Update</a></td>";
                  
                    echo "</tr>";
                    $couter++;
                }
            } else {
                echo "<tr><td colspan='50'>No records found</td></tr>";
            }
            ?>
        </tbody>
    </table>

    </div>

</body>

</html>