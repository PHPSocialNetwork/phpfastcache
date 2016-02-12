<?php

// Use Unicode ? You better put these code in your config files to make sure input and output are UTF-8

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');