/**
 * public/js/quiz-runner.js
 *
 * Drives the whole student-facing quiz flow against the fnr/v1 REST API:
 *   /start-attempt -> render question 1..n -> /submit-question per item
 *   -> /complete-attempt -> results screen.
 * Also handles the separate ungraded Flashcard Review mode via
 * /quiz-flashcards.
 *
 * Question `content` shape this expects (see QuizController::get_public_questions
 * for what the server actually sends - answer keys are stripped there):
 *   mc / sata / matrix : { options: [{value,label}, ...] }
 *   ordered            : { options: [{value,label}, ...] }
 *   numeric             : { unit: '', placeholder: '' }
 *   hotspot             : { image_url: '', image_alt: '', regions: [{id,x,y,width,height,label}] }
 *   rationale (dyad/triad) : { links: [{key,label,options:[{value,label}]}...] }
 */
(function () {
	'use strict';

	const root = document.getElementById('fnr-quiz');
	if (!root) return;

	const quizId       = root.dataset.quizId;
	const timeLimitMin = parseInt(root.dataset.timeLimit, 10) || 0;

	const introEl      = document.getElementById('fnr-quiz-intro');
	const timerContainerEl = document.getElementById('fnr-quiz-timer');
	const runnerEl      = document.getElementById('fnr-quiz-runner');
	const resultsEl      = document.getElementById('fnr-quiz-results');
	const flashcardsEl      = document.getElementById('fnr-quiz-flashcards');
	const startBtn      = document.getElementById('fnr-quiz-start');
	const flashcardsStartBtn = document.getElementById('fnr-quiz-flashcards-start');

	let state = null;   // attempt state, see startAttempt()
	let timerInterval = null;

	// ---------------------------------------------------------------
	// Small helpers
	// ---------------------------------------------------------------

	function normalizeOptions(options) {
		return (options || []).map(function (opt) {
			if (typeof opt === 'string' || typeof opt === 'number') {
				return { value: String(opt), label: String(opt) };
			}
			return {
				value: String(opt.value ?? opt.id ?? opt.label ?? ''),
				label: String(opt.label ?? opt.text ?? opt.value ?? ''),
			};
		});
	}

	function shuffled(arr) {
		const copy = arr.slice();
		for (let i = copy.length - 1; i > 0; i--) {
			const j = Math.floor(Math.random() * (i + 1));
			[copy[i], copy[j]] = [copy[j], copy[i]];
		}
		return copy;
	}

	async function apiFetch(path, options) {
		const response = await fetch(fnrQuizRunner.restUrl + path, Object.assign({
			headers: Object.assign({
				'Content-Type': 'application/json',
				'X-WP-Nonce': fnrQuizRunner.nonce,
			}, (options && options.headers) || {}),
		}, options));

		const data = await response.json();
		if (!response.ok) {
			throw new Error(data.error || fnrQuizRunner.i18n.error);
		}
		return data;
	}

	function el(tag, attrs, children) {
		const node = document.createElement(tag);
		Object.entries(attrs || {}).forEach(function ([key, value]) {
			if (key === 'text') {
				node.textContent = value;
			} else if (key.startsWith('on')) {
				node.addEventListener(key.slice(2).toLowerCase(), value);
			} else {
				node.setAttribute(key, value);
			}
		});
		(children || []).forEach(function (child) {
			if (child) node.appendChild(child);
		});
		return node;
	}

	// ---------------------------------------------------------------
	// Timer
	// ---------------------------------------------------------------

	function startTimer(onExpire) {
		if (timeLimitMin <= 0 || !timerContainerEl) return;

		const endsAt = Date.now() + timeLimitMin * 60 * 1000;
		const timerEl = el('span', { class: 'fnr-quiz-timer', role: 'timer' });

		function tick() {
			const remaining = Math.max(0, endsAt - Date.now());
			const minutes = Math.floor(remaining / 60000);
			const seconds = Math.floor((remaining % 60000) / 1000);
			timerEl.textContent = minutes + ':' + String(seconds).padStart(2, '0');
			timerEl.dataset.urgent = remaining < 60000 ? 'true' : 'false';

			if (remaining <= 0) {
				clearInterval(timerInterval);
				onExpire();
			}
		}

		timerContainerEl.innerHTML = '';
		timerContainerEl.appendChild(el('span', { class: 'fnr-quiz-timer-label', text: fnrQuizRunner.i18n.timeRemaining || '' }));
		timerContainerEl.appendChild(timerEl);
		timerContainerEl.hidden = false;

		tick();
		timerInterval = setInterval(tick, 1000);
	}

	function stopTimer() {
		if (timerInterval) clearInterval(timerInterval);
		timerInterval = null;
		if (timerContainerEl) {
			timerContainerEl.hidden = true;
			timerContainerEl.innerHTML = '';
		}
	}

	// ---------------------------------------------------------------
	// Per-type question renderers.
	// Each returns { el, getResponse() } where getResponse() returns the
	// user_response object to POST to /submit-question.
	// ---------------------------------------------------------------

	const renderers = {
		mc: renderChoice.bind(null, 'radio'),
		matrix: renderChoice.bind(null, 'radio'),
		sata: renderChoice.bind(null, 'checkbox'),

		ordered: function (content) {
			const options = shuffled(normalizeOptions(content.options));
			const list = el('ul', { class: 'fnr-order-list', 'aria-label': fnrQuizRunner.i18n.orderedHint });

			function refresh() {
				Array.from(list.children).forEach(function (li, index) {
					li.querySelector('.fnr-order-position').textContent = (index + 1) + '.';
					li.querySelector('.fnr-order-up').disabled = index === 0;
					li.querySelector('.fnr-order-down').disabled = index === list.children.length - 1;
				});
			}

			options.forEach(function (opt) {
				const li = el('li', { 'data-value': opt.value }, [
					el('span', { class: 'fnr-order-position' }),
					el('span', { class: 'fnr-order-label', text: opt.label }),
					el('span', { class: 'fnr-order-controls' }, [
						el('button', {
							type: 'button', class: 'button fnr-order-up',
							'aria-label': fnrQuizRunner.i18n.moveUp + ' ' + opt.label,
							onClick: function () {
								const prev = li.previousElementSibling;
								if (prev) { list.insertBefore(li, prev); refresh(); li.querySelector('.fnr-order-up').focus(); }
							},
						}, [document.createTextNode('\u2191')]),
						el('button', {
							type: 'button', class: 'button fnr-order-down',
							'aria-label': fnrQuizRunner.i18n.moveDown + ' ' + opt.label,
							onClick: function () {
								const next = li.nextElementSibling;
								if (next) { list.insertBefore(next, li); refresh(); li.querySelector('.fnr-order-down').focus(); }
							},
						}, [document.createTextNode('\u2193')]),
					]),
				]);
				list.appendChild(li);
			});

			refresh();

			return {
				el: el('div', {}, [
					el('p', { class: 'description', text: fnrQuizRunner.i18n.orderedHint }),
					list,
				]),
				getResponse: function () {
					return { selections: Array.from(list.children).map((li) => li.dataset.value) };
				},
			};
		},

		numeric: function (content) {
			const input = el('input', {
				type: 'number', step: 'any', class: 'fnr-quiz-numeric-input',
				id: 'fnr-quiz-numeric-answer',
				placeholder: content.placeholder || '',
				'aria-describedby': content.unit ? 'fnr-quiz-numeric-unit' : null,
			});

			/**
			 * A plain type="number" input doesn't reject a "dirty" paste
			 * (e.g. a whole sentence copied by accident) - browsers just
			 * keep every digit character they find and mash them into one
			 * huge number instead of erroring, which is silently wrong
			 * rather than obviously wrong. Take over paste ourselves and
			 * pull out a single valid number (or nothing) instead.
			 */
			input.addEventListener('paste', function (e) {
				const pasted = (e.clipboardData || window.clipboardData).getData('text');
				const match = pasted.match(/-?\d+(?:\.\d+)?/);
				e.preventDefault();
				input.value = match ? match[0] : '';
			});

			// Put the unit in the label itself ("Your answer (gtt/min)") so
			// it reads as instructions, not as something to type into the
			// box - the box only ever wants the number.
			const labelText = content.unit
				? fnrQuizRunner.i18n.yourAnswer + ' (' + content.unit + ')'
				: fnrQuizRunner.i18n.yourAnswer;

			return {
				el: el('div', { class: 'fnr-quiz-numeric-field' }, [
					el('label', { for: 'fnr-quiz-numeric-answer', text: labelText }),
					el('div', { class: 'fnr-quiz-numeric-wrap' }, [
						input,
						content.unit ? el('span', {
							id: 'fnr-quiz-numeric-unit',
							class: 'fnr-quiz-numeric-unit',
							'aria-hidden': 'true', // already announced via the label
							text: content.unit,
						}) : null,
					]),
				]),
				getResponse: function () {
					return { value: input.value.trim() };
				},
			};
		},

		hotspot: function (content) {
			const multi = content.multiple !== false; // default true (plus_minus-style); set false for single-answer hotspots
			const selected = new Set();

			const stage = el('div', { class: 'fnr-hotspot-stage' }, [
				el('img', { src: content.image_url || '', alt: content.image_alt || '' }),
			]);

			(content.regions || []).forEach(function (region) {
				const btn = el('button', {
					type: 'button',
					class: 'fnr-hotspot-region',
					style: `left:${region.x}%;top:${region.y}%;width:${region.width}%;height:${region.height}%;`,
					'aria-pressed': 'false',
					'aria-label': region.label || '',
					onClick: function () {
						if (selected.has(region.id)) {
							selected.delete(region.id);
							btn.setAttribute('aria-pressed', 'false');
						} else {
							if (!multi) {
								selected.clear();
								stage.querySelectorAll('.fnr-hotspot-region').forEach((b) => b.setAttribute('aria-pressed', 'false'));
							}
							selected.add(region.id);
							btn.setAttribute('aria-pressed', 'true');
						}
					},
				});
				stage.appendChild(btn);
			});

			return {
				el: el('div', {}, [
					stage,
					el('p', { class: 'description', text: fnrQuizRunner.i18n.hotspotHint }),
				]),
				getResponse: function () {
					return { selections: Array.from(selected) };
				},
			};
		},

		rationale: function (content) {
			const links = content.links || [];
			const selects = {};

			const wrap = el('div', { class: 'fnr-quiz-links' });

			links.forEach(function (link) {
				const select = el('select', { id: 'fnr-quiz-link-' + link.key });
				select.appendChild(el('option', { value: '', text: fnrQuizRunner.i18n.selectOne }));
				normalizeOptions(link.options).forEach(function (opt) {
					select.appendChild(el('option', { value: opt.value, text: opt.label }));
				});
				selects[link.key] = select;

				wrap.appendChild(el('label', { for: 'fnr-quiz-link-' + link.key }, [
					document.createTextNode(link.label || link.key),
					select,
				]));
			});

			return {
				el: wrap,
				getResponse: function () {
					const response = {};
					Object.keys(selects).forEach(function (key) {
						response[key] = selects[key].value;
					});
					return response;
				},
			};
		},
	};

	function renderChoice(inputType, content) {
		const options = normalizeOptions(content.options);
		const name = 'fnr-quiz-choice-' + Math.random().toString(36).slice(2);
		const list = el('ul', { class: 'fnr-quiz-options' });

		options.forEach(function (opt, i) {
			const input = el('input', { type: inputType, name: name, value: opt.value, id: name + '-' + i });
			list.appendChild(el('li', {}, [
				el('label', { for: name + '-' + i }, [input, document.createTextNode(opt.label)]),
			]));
		});

		return {
			el: list,
			getResponse: function () {
				const checked = Array.from(list.querySelectorAll('input:checked')).map((i) => i.value);
				return { selections: checked };
			},
		};
	}

	// ---------------------------------------------------------------
	// Quiz attempt flow
	// ---------------------------------------------------------------

	async function startAttempt(mode) {
		startBtn.disabled = true;
		startBtn.setAttribute('aria-busy', 'true');

		try {
			const data = await apiFetch('start-attempt', {
				method: 'POST',
				body: JSON.stringify({ quiz_id: quizId }),
			});

			state = {
				attemptId: data.attempt_id,
				questions: data.questions,
				index: 0,
				mode: mode,
				startedAt: Date.now(),
			};

			introEl.hidden = true;
			runnerEl.hidden = false;

			startTimer(function () {
				completeAttempt(); // time's up - submit whatever's answered
			});

			renderCurrentQuestion();
		} catch (err) {
			startBtn.disabled = false;
			startBtn.removeAttribute('aria-busy');
			alert(err.message); // eslint-disable-line no-alert -- rare path (network failure before anything else is on screen)
		}
	}

	function renderCurrentQuestion() {
		runnerEl.innerHTML = '';

		const question = state.questions[state.index];
		const renderer = renderers[question.type] || renderers[question.scoring_rule] || renderers.mc;
		const rendered = renderer(question.content || {});

		const progress = el('p', { class: 'fnr-quiz-progress' }, [
			el('span', {
				text: fnrQuizRunner.i18n.questionOf
					.replace('%1$d', state.index + 1)
					.replace('%2$d', state.questions.length),
			}),
		]);

		const submitBtn = el('button', {
			type: 'button', class: 'button button-primary',
			text: fnrQuizRunner.i18n.submitAnswer,
			onClick: function () { submitCurrentAnswer(rendered.getResponse(), submitBtn); },
		});

		runnerEl.appendChild(progress);
		runnerEl.appendChild(el('div', { class: 'fnr-quiz-question' }, [
			el('p', { class: 'fnr-quiz-prompt', text: question.prompt }),
			rendered.el,
		]));
		runnerEl.appendChild(el('div', { class: 'fnr-quiz-nav' }, [submitBtn]));

		submitBtn.focus();
	}

	async function submitCurrentAnswer(userResponse, submitBtn) {
		submitBtn.disabled = true;
		submitBtn.setAttribute('aria-busy', 'true');

		const question = state.questions[state.index];

		try {
			const data = await apiFetch('submit-question', {
				method: 'POST',
				body: JSON.stringify({
					attempt_id: state.attemptId,
					question_id: question.question_id,
					user_response: userResponse,
				}),
			});

			if (state.mode === 'practice') {
				showFeedback(data);
			} else {
				advance();
			}
		} catch (err) {
			submitBtn.disabled = false;
			submitBtn.removeAttribute('aria-busy');
			const feedback = el('p', { role: 'alert', text: err.message });
			runnerEl.appendChild(feedback);
		}
	}

	function showFeedback(data) {
		const isCorrect = !!data.result.is_correct;

		const feedback = el('div', {
			class: 'fnr-quiz-feedback',
			'data-correct': isCorrect ? 'true' : 'false',
			role: 'status',
		}, [
			el('p', {}, [
				el('span', { class: 'fnr-quiz-feedback-icon', 'aria-hidden': 'true', text: isCorrect ? '\u2713 ' : '\u2717 ' }),
				document.createTextNode(isCorrect ? fnrQuizRunner.i18n.correct : fnrQuizRunner.i18n.incorrect),
			]),
			data.rationale ? el('p', { class: 'fnr-quiz-rationale', text: data.rationale }) : null,
		]);

		const isLast = state.index === state.questions.length - 1;
		const nextBtn = el('button', {
			type: 'button', class: 'button button-primary',
			text: isLast ? fnrQuizRunner.i18n.viewResults : fnrQuizRunner.i18n.nextQuestion,
			onClick: function () { isLast ? completeAttempt() : advance(); },
		});

		runnerEl.querySelector('.fnr-quiz-nav').replaceChildren(nextBtn);
		runnerEl.appendChild(feedback);
		nextBtn.focus();
	}

	function advance() {
		if (state.index < state.questions.length - 1) {
			state.index += 1;
			renderCurrentQuestion();
		} else {
			completeAttempt();
		}
	}

	async function completeAttempt() {
		stopTimer();
		runnerEl.innerHTML = '<p aria-live="polite">' + fnrQuizRunner.i18n.saving + '</p>';

		const timeSpent = Math.round((Date.now() - state.startedAt) / 1000);

		try {
			const data = await apiFetch('complete-attempt', {
				method: 'POST',
				body: JSON.stringify({ attempt_id: state.attemptId, time_spent_seconds: timeSpent }),
			});

			runnerEl.hidden = true;
			resultsEl.hidden = false;
			resultsEl.dataset.passed = data.passing_status === 'passed' ? 'true' : 'false';

			resultsEl.innerHTML = '';
			resultsEl.appendChild(el('span', { class: 'fnr-quiz-score', text: Math.round(data.percentage) + '%' }));
			resultsEl.appendChild(el('p', {
				text: data.passing_status === 'passed' ? fnrQuizRunner.i18n.passed : fnrQuizRunner.i18n.failed,
			}));
			resultsEl.appendChild(el('p', {
				text: fnrQuizRunner.i18n.scoreDetail
					.replace('%1$s', data.score_earned)
					.replace('%2$s', data.total_possible)
					.replace('%3$s', data.pass_threshold),
			}));

			const reviewSection = el('div', { class: 'fnr-quiz-review', hidden: 'hidden' });
			renderReview(reviewSection, data.review || []);

			const reviewBtn = el('button', {
				type: 'button', class: 'button',
				text: fnrQuizRunner.i18n.reviewAnswers,
				onClick: function () {
					const hidden = reviewSection.hasAttribute('hidden');
					if (hidden) {
						reviewSection.removeAttribute('hidden');
						reviewBtn.textContent = fnrQuizRunner.i18n.hideReview;
					} else {
						reviewSection.setAttribute('hidden', 'hidden');
						reviewBtn.textContent = fnrQuizRunner.i18n.reviewAnswers;
					}
				},
			});

			const retakeBtn = el('button', {
				type: 'button', class: 'button button-primary',
				text: fnrQuizRunner.i18n.retakeQuiz,
				onClick: resetToIntro,
			});

			const actions = [reviewBtn, retakeBtn];

			// Only present when QuizCourseMetaBox has this quiz assigned
			// to a course - an unassigned quiz has nowhere to send them.
			if (fnrQuizRunner.courseUrl) {
				actions.push(el('a', {
					class: 'button fnr-quiz-back-to-course',
					href: fnrQuizRunner.courseUrl,
					text: fnrQuizRunner.i18n.backToCourse.replace('%s', fnrQuizRunner.courseTitle),
				}));
			}

			resultsEl.appendChild(el('div', { class: 'fnr-quiz-results-actions' }, actions));
			resultsEl.appendChild(reviewSection);
		} catch (err) {
			runnerEl.innerHTML = '<p role="alert">' + err.message + '</p>';
		}
	}

	function renderReview(container, items) {
		if (!items.length) {
			container.appendChild(el('p', { text: fnrQuizRunner.i18n.noReview }));
			return;
		}

		items.forEach(function (item, i) {
			container.appendChild(el('div', { class: 'fnr-quiz-review-item', 'data-correct': item.is_correct ? 'true' : 'false' }, [
				el('p', { class: 'fnr-quiz-review-prompt' }, [
					el('span', { class: 'fnr-quiz-review-icon', 'aria-hidden': 'true', text: item.is_correct ? '\u2713 ' : '\u2717 ' }),
					document.createTextNode((i + 1) + '. ' + item.prompt),
				]),
				el('p', { class: 'fnr-quiz-review-your-answer', text: fnrQuizRunner.i18n.yourAnswerLabel + ' ' + item.your_answer }),
				item.is_correct ? null : el('p', { class: 'fnr-quiz-review-correct-answer', text: fnrQuizRunner.i18n.correctAnswerLabel + ' ' + item.correct_answer }),
				item.rationale ? el('p', { class: 'fnr-quiz-review-rationale', text: item.rationale }) : null,
			]));
		});
	}

	/**
	 * "Retake Quiz" on the results screen - without this, finishing an
	 * attempt was a dead end: reloading the page was the only way to
	 * start over, and there was no way back to the intro screen at all.
	 */
	function resetToIntro() {
		resultsEl.hidden = true;
		resultsEl.innerHTML = '';
		runnerEl.innerHTML = '';
		introEl.hidden = false;
		state = null;
		startBtn.disabled = false;
		startBtn.removeAttribute('aria-busy');
		startBtn.focus();
	}

	// ---------------------------------------------------------------
	// Flashcard Review (ungraded study mode)
	// ---------------------------------------------------------------

	async function startFlashcards() {
		flashcardsStartBtn.disabled = true;

		try {
			const data = await apiFetch('quiz-flashcards?quiz_id=' + encodeURIComponent(quizId), { method: 'GET' });

			if (!data.cards.length) {
				alert(fnrQuizRunner.i18n.noFlashcards); // eslint-disable-line no-alert
				flashcardsStartBtn.disabled = false;
				return;
			}

			introEl.hidden = true;
			flashcardsEl.hidden = false;
			renderFlashcards(data.cards);
		} catch (err) {
			flashcardsStartBtn.disabled = false;
			alert(err.message); // eslint-disable-line no-alert
		}
	}

	function renderFlashcards(cards) {
		let index = 0;

		const card = el('div', {
			class: 'fnr-flashcard', tabindex: '0', role: 'button',
			'aria-label': fnrQuizRunner.i18n.flipCard,
			'data-flipped': 'false',
		}, [
			el('div', { class: 'fnr-flashcard-face fnr-flashcard-face--front' }),
			el('div', { class: 'fnr-flashcard-face fnr-flashcard-face--back' }),
		]);

		const counter = el('span', {});
		const prevBtn = el('button', { type: 'button', class: 'button', text: fnrQuizRunner.i18n.previous });
		const nextBtn = el('button', { type: 'button', class: 'button', text: fnrQuizRunner.i18n.next });

		// Without this, reaching either end just silently disables the
		// only two buttons on screen with no indication why, and no way
		// back to the quiz intro short of reloading the page.
		const exitBtn = el('button', {
			type: 'button', class: 'button fnr-flashcard-exit',
			text: fnrQuizRunner.i18n.exitFlashcards,
			onClick: exitFlashcards,
		});

		function render() {
			card.dataset.flipped = 'false';
			card.querySelector('.fnr-flashcard-face--front').textContent = cards[index].prompt;
			card.querySelector('.fnr-flashcard-face--back').textContent = cards[index].rationale || fnrQuizRunner.i18n.noRationale;
			counter.textContent = fnrQuizRunner.i18n.questionOf
				.replace('%1$d', index + 1)
				.replace('%2$d', cards.length);
			prevBtn.disabled = index === 0;
			nextBtn.disabled = index === cards.length - 1;
		}

		function flip() {
			card.dataset.flipped = card.dataset.flipped === 'true' ? 'false' : 'true';
		}

		card.addEventListener('click', flip);
		card.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); flip(); }

			// Same reachability gap as the buttons: without bounds checks
			// here, arrow keys at either end would throw on cards[-1] /
			// cards[cards.length] instead of just stopping.
			if (e.key === 'ArrowRight' && index < cards.length - 1) { e.preventDefault(); index += 1; render(); }
			if (e.key === 'ArrowLeft' && index > 0) { e.preventDefault(); index -= 1; render(); }
		});

		prevBtn.addEventListener('click', function () { if (index > 0) { index -= 1; render(); } });
		nextBtn.addEventListener('click', function () { if (index < cards.length - 1) { index += 1; render(); } });

		render();

		flashcardsEl.innerHTML = '';
		flashcardsEl.appendChild(card);
		flashcardsEl.appendChild(el('div', { class: 'fnr-flashcard-nav' }, [prevBtn, counter, nextBtn]));
		flashcardsEl.appendChild(el('div', { class: 'fnr-flashcard-exit-row' }, [exitBtn]));
	}

	function exitFlashcards() {
		flashcardsEl.hidden = true;
		flashcardsEl.innerHTML = '';
		introEl.hidden = false;
		flashcardsStartBtn.disabled = false;
		flashcardsStartBtn.focus();
	}

	// ---------------------------------------------------------------
	// Wire up entry points
	// ---------------------------------------------------------------

	if (startBtn) {
		startBtn.addEventListener('click', function () {
			const mode = document.querySelector('input[name="fnr_quiz_mode"]:checked');
			startAttempt(mode ? mode.value : 'practice');
		});
	}

	if (flashcardsStartBtn) {
		flashcardsStartBtn.addEventListener('click', startFlashcards);
	}
})();
