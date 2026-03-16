<?php

$şifre = "123456";

$hash = password_hash($şifre, PASSWORD_BCRYPT);

echo $hash;