<?php
 use Forge\Core\Helpers\Format;

 $errorCode = isset($errorCode) ? htmlspecialchars($errorCode) : null;
 $errorMessage = '';
 $pageTitle = '';
 $displayCode = '';

 switch ($errorCode) {
     case 400:
         $displayCode = '400';
         $pageTitle = 'Bad Request';
         $errorMessage = 'The server could not understand your request.';
         break;
     case 401:
         $displayCode = '401';
         $pageTitle = 'Unauthorized';
         $errorMessage = 'You are not authorized to view this page. Please log in.';
         break;
     case 403:
         $displayCode = '403';
         $pageTitle = 'Forbidden';
         $errorMessage = 'You do not have permission to access this resource.';
         break;
     case 404:
         $displayCode = '404';
         $pageTitle = 'Oops! Page not found';
         $errorMessage = 'The page you are looking for could not be found.';
         break;
     case 500:
         $displayCode = '500';
         $pageTitle = 'Internal Server Error';
         $errorMessage = 'Something went wrong on our server. Please try again later.';
         break;
     case 503:
         $displayCode = '503';
         $pageTitle = 'Service Unavailable';
         $errorMessage = 'The server is currently unavailable. Please try again later.';
         break;
     default:
         $displayCode = 'Oops!';
         $pageTitle = 'An Unexpected Error Occurred';
         $errorMessage = 'An unexpected error has occurred.';
         break;
 }
 ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$errorCode?> <?=$pageTitle?></title>
    <style>
    * {
        -webkit-box-sizing: border-box;
        box-sizing: border-box;
    }

    body {
        padding: 0;
        margin: 0;
    }

    #notfound {
        position: relative;
        height: 100vh;
    }

    #notfound .notfound {
        position: absolute;
        left: 50%;
        top: 50%;
        -webkit-transform: translate(-50%, -50%);
        -ms-transform: translate(-50%, -50%);
        transform: translate(-50%, -50%);
    }

    .notfound {
        max-width: 520px;
        width: 100%;
        line-height: 1.4;
        text-align: center;
    }

    .notfound .notfound-404 {
        position: relative;
        height: 240px;
    }

    .notfound .notfound-404 h1 {
        font-family: 'Montserrat', sans-serif;
        position: absolute;
        left: 50%;
        top: 50%;
        -webkit-transform: translate(-50%, -50%);
        -ms-transform: translate(-50%, -50%);
        transform: translate(-50%, -50%);
        font-size: 252px;
        font-weight: 900;
        margin: 0px;
        color: #262626;
        text-transform: uppercase;
        letter-spacing: -40px;
        margin-left: -20px;
    }

    .notfound .notfound-404 h1>span {
        text-shadow: -8px 0px 0px #fff;
    }

    .notfound .notfound-404 h3 {
        font-family: 'Cabin', sans-serif;
        position: relative;
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        color: #262626;
        margin: 0px;
        letter-spacing: 3px;
        padding-left: 6px;
    }

    .notfound h2 {
        font-family: 'Cabin', sans-serif;
        font-size: 20px;
        font-weight: 400;
        text-transform: uppercase;
        color: #000;
        margin-top: 0px;
        margin-bottom: 25px;
    }

    @media only screen and (max-width: 767px) {
        .notfound .notfound-404 {
            height: 200px;
        }

        .notfound .notfound-404 h1 {
            font-size: 200px;
        }
    }

    @media only screen and (max-width: 480px) {
        .notfound .notfound-404 {
            height: 162px;
        }

        .notfound .notfound-404 h1 {
            font-size: 162px;
            height: 150px;
            line-height: 162px;
        }

        .notfound h2 {
            font-size: 16px;
        }
    }
    </style>
</head>

<body>
    <div class="error-container">
        <div id="notfound">
            <div class="notfound">
                <div class="notfound-404">
                    <h3><?=$pageTitle?></h3>
                    <?= Format::errorCode($errorCode) ?>
                </div>
                <h2><?=$errorMessage?></h2>
                <p><a href="javascript:history.back()">Go Back</a></p>
            </div>
        </div>
    </div>
</body>

</html>