<?php

function hook_brukar_client_user($edit) {
  return user_load_by_mail($edit['mail']);
}