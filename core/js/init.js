(function () {
    function runNoticeFade() {
        setTimeout(function () {
            var notice = document.getElementById('ninetyplus-cache-notice');
            if (notice) {
                notice.style.transition = "opacity 0.8s ease";
                notice.style.opacity = "0";
                setTimeout(function () {
                    notice.remove();
                }, 800);
            }
        }, 2000);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", runNoticeFade);
    } else {
        runNoticeFade();
    }
})();

window.WPNinetyPlusGetImages = function () {
    console.log("Contact Administrator to enable");
};

window.WPNinetyPlusAssetsPaint = function () {
    console.log("Contact Administrator to enable");
};

window.WPNinetyPlusAssetsInteractive = function () {
    console.log("Contact Administrator to enable");
};

window.WPNinetyPlusAsstesLayout = function () {
    console.log("Contact Administrator to enable");
};

window.WPNinetyPlusMetaData = function () {
    console.log("Contact Administrator to enable");
};