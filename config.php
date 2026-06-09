<?php

require_once __DIR__ . '/config/env.php';

define('TAVILY_API_KEY', (string) envValue('TAVILY_API_KEY', ''));
