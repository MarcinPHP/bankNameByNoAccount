<?php
	header("Content-Type: text/html; charset=utf-8");
?><!DOCTYPE html>
<html lang="pl">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<meta name="robots" content="index, follow" />
    <title>Bank Account number</title>
</head>
<body>
<?php
	require("./nrb.class.php");
	
	// w każdej funkcji można podać nr konta, ale jeśli następna funkcja odnosi się do poprzednio podanego numer konta nie trzeba go ponownie podawać.
	$nrb = new nrb("02213000042001047755080001");
	
	$nrAccount = $nrb->format();
	
	$correct = ($nrb->correct() ? "Tak" : "Nie");
	
	$data = $nrb->decodeName();
	
	// print_r($nrb->getBanks()); -- zwraca do tablicy wszystkie dane banków. Kluczem do danych konkretnego banku jest Numer rozliczeniowy oddzialu banku
?>

<div>Numer konta bankowego: <strong><?php echo $nrAccount; ?></strong></div>
<div>Czy numer jest prawidłowy: <strong><?php echo $correct; ?></strong></div>
Dane baku:
<pre><?php print_r($data); ?></pre>

</body>
</html>