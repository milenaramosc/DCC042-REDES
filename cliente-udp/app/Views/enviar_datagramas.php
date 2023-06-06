<!DOCTYPE html>
<html lang="pt-br" class="h-100">
<head>
    <meta charset="UTF-8">
    <title>Enviar datagramas</title>
    <link rel="stylesheet" type="text/css" href="/bootstrap-5.3.0-dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/enviar_datagrama/css/styles.css">
</head>
<body class="h-100">
    <div class="d-flex flex-column align-items-center justify-content-center h-100">
        <div class="container-externo">
            <h1 class="texto">Enviar datagramas</h1>
            <div class="d-flex flex-column justify-content-between align-items-center container-interno">
                <div id="animContainer" class="p-5"> </div>
    
                <!-- <form action="/enviar_datagramas" method="post"> -->
                    <div class="d-flex flex-column align-items-center p-5">
                        <div class="input-group mb-3">
                            <input type="file" class="form-control" id="datagrama">
                            <label class="input-group-text" for="datagrama">Upload</label>
                        </div>
                        <button type="submit" class="btn btn-primary" id="enviar">Enviar</button>
                    </div>
                <!-- </form> -->
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.7.5/lottie.min.js'></script>
    <script  src="/enviar_datagrama/js/script.js"></script>
</body>
</html>
