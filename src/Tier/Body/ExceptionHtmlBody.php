<?php

namespace Tier\Body;

use Room11\HTTP\Body;

/**
 * Class ExceptionHtmlBody
 *
 * We use this class to generate exception pages rather than the template renderer, to
 * avoid yo' dawging if the template renderer has an exception.
 *
 * @package Tier\ResponseBody
 */
class ExceptionHtmlBody implements Body
{
    private $text;
    private $statusCode;
    
    public function __construct($exceptionString, $statusCode)
    {
        $fullText = $this->getBeforeText();
        $fullText .= nl2br($exceptionString);
        $fullText .= $this->getAfterText();

        $this->text = $fullText;
        $this->statusCode = $statusCode;
    }
    
    /**
     * Responsible for outputting entity body data to STDOUT
     */
    public function sendData()
    {
        echo $this->text;
    }

    /**
     * Get the body data as a string. This might be non-conformant
     * @return string
     */
    public function getData()
    {
        return $this->text;
    }
    
    
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return [
            'Content-Type' => 'text/html; charset=UTF-8; charset=utf-8',
            'Content-Length' => strlen($this->text)
        ];
    }

    /**
     * @return string
     */
    private function getBeforeText()
    {
        $text = <<< END

<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <title>Tier - DI based application</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel='stylesheet' type='text/css' href='/css/bootstrap.min.css' />
    <link rel='stylesheet' type='text/css' href='/css/bootstrap-theme.css' />
    <script type='text/javascript' src='/js/bootstrap.js'></script>
</head>

<body>
<div class="container">
  
<div class="row">
  <div class="col-md-10 col-md-offset-2">
        <div class="page-header">
            <h1>Tier <small>using DI to implement app architecture</small></h1>
        </div>
  </div>
</div>

<div class="row">
    <div class="col-md-2">


<nav class="bs-docs-sidebar hidden-print">
    <ul class="nav">
        <li>
            <a href="/">Home</a>
        </li>
    </ul>
</nav>
    </div>
    
    <div class="col-md-9">
         <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
END;

        return $text;
    }

    /**
     * @return string
     */
    private function getAfterText()
    {
        $text = <<< END
            </div>
        </div>
    </div> 
    </div>
</div>
</body>
</html>

END;

        return $text;
    }
}
