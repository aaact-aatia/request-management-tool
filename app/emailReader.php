<?php
/**
 * Email Reader - Parse accessibility request emails
 */

// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
    session_start();
}

// Grab HTTPS check
require('includes/httpscheck.php');
require('sql.php');
/** @var mysqli $link */

// Check if there is a status
if (!empty($_GET['status'])){
    $status = $_GET['status'];
}
else{
    $status = "";
}

$parsedData = [];
$patterns = [
    'reference_number' => '/reference number (\d+)/i',
    'reporter_name' => '/Reporter\s+-+\s+(.+)/i',
    'phone_number' => '/Phone Number\s+-+\s+([\d-]+)/i',
    'system_application' => '/System\/Application\s+-+\s+(.+)/i',
    'computer_number' => '/Operating System.*-\s*(\S+)/',
    'A1' => '/A1\.\-\s*(.*)/i',
    'A2' => '/A2\.\-\s*(.*)/i',
    'A3' => '/A3\.\-\s*(.*)/i',
    'R1' => '/R1\.\-\s*(.*)/i',
    'R2' => '/R2\.\-\s*(.*)/i',
    'R3' => '/R3\.\-\s*(.*)/i',
    'detailed_comments' => '/Detailed comments:\s*(.*?)\s*Questions and Choices completed:/s',
    'commentaire_detailles' => '/Les commentaires détaillés:\s*(.*?)\s*Questions et Choix complétés:/s'
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputString = $_POST["inputText"];
    foreach ($patterns as $key => $pattern) {
        preg_match($pattern, $inputString,$matches);
        $parsedData[$key] = $matches[1] ?? null;
    }
    echo '<pre>';
    print_r($parsedData);
    echo '</pre>';

    $result = mysqli_query($link, "SELECT * FROM tblservices WHERE catalogueid = '2' and status = 1");
	$dbValues_en = [];
    $dbValues_fr = [];

    while ($row = mysqli_fetch_array($result)) {
    $dbValues_en[] = strtolower($row['nameen']);
    $dbValues_fr[] = strtolower($row['namefr']);
    }
    $first_answer = !empty($parsedData['A1']) ? $parsedData['A1'] : $parsedData['R1'];
    $first_answer = strtolower($first_answer);
    $foundInEn = false;
    foreach($dbValues_en as $val) {
        if (str_contains($first_answer, $val) !== false) {
        $foundInEn = true;
        break;
        }
    }

    // Check in $dbValues_fr
    $foundInFr = false;
    foreach($dbValues_fr as $val) {
        if (str_contains($first_answer, $val) !== false) {
        $foundInFr = true;
        break;
        }
    }


    

    

    $typeofcatalogue = "";
    echo '<br><br>';

    if(str_contains($first_answer , "accessibility centre of excellence") || str_contains($first_answer , "centre d’excellence en accessibilité")){       
    echo '<pre>';
    echo "AAACT";
    echo '</pre>';
    }else if(str_contains(haystack: $first_answer, needle: "hardware") || str_contains(haystack: $first_answer, needle: "matériel")){ // Matériel: Ordinateur de bureau [déplacement, disque dur, cpu, carte video, gpu, mémoire vive] 
        echo '<pre>';
        echo "hardware";
        echo '</pre>';      
        $typeofcatalogue = "Loan Bank";
    }else if(str_contains($first_answer, "duty to accommodate")){
        echo '<pre>';
        echo "duto to accomadate";
        echo '</pre>'; 
        $typeofcatalogue = "Loan Bank";
    }
    else if($foundInFr || $foundInEn){
        echo '<pre>';
        echo $first_answer;
        echo '</pre>';
    }else{
        echo '<pre>';
        echo "It's a random";
        echo '</pre>';
    }



}

// Load config
require_once 'includes/config.php';

// Page-specific metadata
$pageTitle = 'Email Reader';
$pageDescription = '';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
    <main role="main" property="mainContentOfPage" class="container">
        <div class="row mrgn-tp-lg">
            <section class="col-md-12">
                <form action="emailReader.php" method="post">
                    <textarea name="inputText" rows="20" cols="80"></textarea><br>
                    <input type="submit" value="Submit">
                </form>
            </section>
        </div>
<?php include 'includes/template/page-details.php'; ?>
    </main>
<?php 
include 'includes/template/footer.php';
include 'includes/template/scripts.php';

// Close connection
mysqli_close($link);