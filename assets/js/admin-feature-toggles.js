(function () {
  "use strict";

  var toggles = document.querySelectorAll("[data-tnstack-feature-toggle]");

  function syncFeature(toggle) {
    var feature = toggle.getAttribute("data-tnstack-feature-toggle");
    var panels = document.querySelectorAll(
      '[data-tnstack-feature-panel="' + feature + '"]',
    );

    panels.forEach(function (panel) {
      panel.hidden = !toggle.checked;
    });
  }

  toggles.forEach(function (toggle) {
    syncFeature(toggle);
    toggle.addEventListener("change", function () {
      syncFeature(toggle);
    });
  });
})();
