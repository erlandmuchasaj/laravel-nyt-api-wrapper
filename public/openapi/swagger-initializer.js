window.onload = function() {
  //<editor-fold desc="Changeable Configuration Block">
  // the following lines will be replaced by docker/configurator, when it runs in a docker-container
  window.ui = SwaggerUIBundle({
      url: window.location.protocol + '//' + window.location.hostname + (window.location.port ? ':' + window.location.port: '') + '/openapi/swagger.json',
      dom_id: '#swagger-ui',
      deepLinking: true,
      docExpansion: 'none',
      displayOperationId: 1,
      displayRequestDuration: 1,
      presets: [
        SwaggerUIBundle.presets.apis,
        SwaggerUIStandalonePreset
      ],
      layout: "StandaloneLayout"
  });

  //</editor-fold>
};
