// public/js/video-progress.js
(function () {
	'use strict';

	const wrapper = document.querySelector('.fnr-video-wrapper');
	if (!wrapper) return;

	const provider = wrapper.dataset.fnrVideoProvider;
	const iframe = document.getElementById('fnr-video-player');
	const COMPLETE_THRESHOLD = 0.9;
	let fired = false;

	function fireComplete() {
		if (fired) return;
		fired = true;
		document.dispatchEvent(new CustomEvent('fnr:video-watched'));
	}

	if (provider === 'youtube') {
		const tag = document.createElement('script');
		tag.src = 'https://www.youtube.com/iframe_api';
		document.head.appendChild(tag);

		window.onYoutubeIframeAPIReady = function () {
			const player = new YT.Player(iframe, {
				events: {
					onStateChange: function (event) {
						if (event.data === YT.PlayerState.PLAYING) {
							const interval = setInterval(function () {
								if (!player.getDuration || player.getDuration() === 0) return;
								const ratio = player.getCurrentTime() / player.getDuration();
								if (ratio >= COMPLETE_THRESHOLD) {
									fireComplete();
									clearInterval(interval);
								}
							}, 2000);
						}
					},
				},
			});
		};

	} else if(provider === 'vimeo') {
		window.addEventListener('message', function (event) {
			if (event.origin !== 'https://player.vimeo.com') return;

			let data;
			try {
				data = JSON.parse(event.data);
			} catch (error) {
				return;
			}

			if (data.event === 'timeupdate' && data.data.percent >= COMPLETE_THRESHOLD) {
				fireComplete();
			}
		});
	}
})();
