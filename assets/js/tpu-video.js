/**
 * Travelpont Úticélok – kattintásra töltő YouTube-beágyazás
 *
 * A .tpu-video-doboz kezdetben csak egy bélyegképet mutat (gyors oldal,
 * nincs YouTube-kérés betöltéskor); kattintásra cseréljük le a lejátszóra,
 * adatkímélő youtube-nocookie beágyazással.
 */
(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        var gomb = e.target.closest('.tpu-video-inditas');
        if (!gomb) return;

        var doboz = gomb.closest('.tpu-video-doboz');
        var id = doboz ? doboz.getAttribute('data-youtube') : '';
        if (!/^[A-Za-z0-9_-]{6,15}$/.test(id)) return;

        var iframe = document.createElement('iframe');
        iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1';
        iframe.title = 'YouTube-videó';
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
        iframe.setAttribute('allowfullscreen', '');
        doboz.replaceChildren(iframe);
    });
})();
