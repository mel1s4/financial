<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title> Viroz Financial </title>
  <?php
    $plugin = str_replace('/views','',plugin_dir_url( __FILE__ ));
    $styles = $plugin . 'styles/styles.css';
  ?>
  <link rel="stylesheet" href="<?php echo $styles ?>">

  <link rel="shortcut icon" href="<?php echo $plugin . 'assets/favicon-01.png' ?>">
</head>
<body id="viroz-financial">
  <?php include('dashboard.php'); ?>
</body>
</html>