<?php
include_once __DIR__ . '/../config.php';

$nom="";
$code="";
$semester="";
$volume_hours="";
$errorMessage="";
$succesMessage="";
if($_SERVER['REQUEST_METHOD']=='POST'){
    $nom=$_POST['nom'];
    $code=$_POST['code'];
    $semester=$_POST['semester'];
    $volume_hours=$_POST['volume_hours'];
    do{
      if(empty($nom) || empty($code) || empty($semester)|| empty($volume_hours) ){
        $errorMessage= "Tous les champs sont obligatoires";
        break;
      }
      $sql="INSERT INTO units  (name,code,semester,volume_hours)  VALUES('$nom','$code','$semester',$volume_hours)";
      $result= $connection->query($sql);
      if(!$result){
        $errorMessage="Invalid query" .$connection->error;
        break;
      }
      $nom="";
      $code="";
      $semester="";
      $volume_hours="";
      $succesMessage="UE bien  ajoutée";
      header("location: /PROJECT/UE/index.php?success=" . urlencode($succesMessage));
      exit;

    }while(false);
}






?>






<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container my-5">
        <h2>Nouveau UE</h2>
        <?php
if (!empty($errorMessage)) {
    echo "
    <div class='alert alert-warning alert-dismissible fade show' role='alert'>
        <strong>$errorMessage</strong>
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
    </div>";
}
?>

        <form method="post">
            <div class="row mb-3">
                <label  class="col-sm-3 col-form-label ">NOM</label>
                <div class="col-sm-3">
                    <input type="text" class="form-control" name="nom" value="<?php echo "$nom"?>">
                </div>
            </div>
            <div class="row mb-3">
                <label  class="col-sm-3 col-form-label ">CODE</label>
                <div class="col-sm-3">
                    <input type="text" class="form-control" name="code" value="<?php echo "$code"?>">
                </div>
            </div>
            <div class="row mb-3">
    <label class="col-sm-3 col-form-label">SEMESTRE</label>
    <div class="col-sm-3">
        <input type="text" class="form-control" name="semester" value="<?php echo $semester ?>">
    </div>
</div>

<div class="row mb-3">
    <label class="col-sm-3 col-form-label">VOLUME HORAIRE</label>
    <div class="col-sm-3">
        <input type="number" class="form-control" name="volume_hours" value="<?php echo $volume_hours ?>">
    </div>
</div>

            <div class="row mb-3">
                <div class="offset-sm-3 col-sm-3 d-grid">
                    <button type="submit" class="btn btn-primary">
                    Soumettre</button>
                </div>
                <div class="offset-sm-3 col-sm-3 d-grid">
                    <a href="/PROJECT/UE/index.php" class="btn btn-outline-primary" role="button">Retour</a>
                </div>
            </div>

        </form>
    </div>
    
</body>
</html>