<?php
// Aici scrii parola pe care o vrei tu reala
$parola_mea = 'AdminAlvoro2026!@'; 
echo password_hash($parola_mea, PASSWORD_DEFAULT);
?>