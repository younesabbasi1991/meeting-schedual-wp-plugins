document.querySelectorAll('.wpms-read-more-container').forEach(container => {
    const content = container.querySelector('.wpms-read-more-content');
    const btn = container.querySelector('.wpms-toggle-btn');

    if (content.scrollHeight <= 50) {
        btn.style.display = 'none';
        return;
    }

    btn.addEventListener('click', function() {
        const isExpanded = content.classList.contains('expanded');
        if (isExpanded) {
            content.classList.remove('expanded');
            this.textContent = 'مشاهده محتوا';
        } else {
            content.classList.add('expanded');
            this.textContent = 'بستن';
        }
    });
});

(function() {
    // تبدیل ثانیه به HH:MM:SS
    function formatTime(seconds) {
        let isNegative = seconds < 0;
        let absSeconds = Math.abs(seconds);
        let hours = Math.floor(absSeconds / 3600);
        let minutes = Math.floor((absSeconds % 3600) / 60);
        let secs = absSeconds % 60;
        let formatted = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        return isNegative ? '-' + formatted : formatted;
    }

    let timersData = {}; // کلید = id نمایشگر
    let currentActiveTimerId = null;

    // توقف کامل تایمر (بدون تغییر دکمه)
    function forceStopTimer(displayElement) {
        const id = displayElement.id;
        if (timersData[id] && timersData[id].intervalId) {
            clearInterval(timersData[id].intervalId);
            timersData[id].isRunning = false;
            timersData[id].intervalId = null;
        }
        if (currentActiveTimerId === id) currentActiveTimerId = null;
    }

    // توقف تایمر و ریست دکمه به حالت "فعال" (برای توقف ارادی)
    function stopTimerAndResetButton(displayElement, runButton) {
        const id = displayElement.id;
        if (timersData[id] && timersData[id].intervalId) {
            clearInterval(timersData[id].intervalId);
            const box = displayElement.closest('.task-box');
            if (box) box.classList.remove('active-pulse');
            timersData[id].isRunning = false;
            timersData[id].intervalId = null;
        }
        if (currentActiveTimerId === id) currentActiveTimerId = null;
        if (runButton && !timersData[id]?.isEnded) {
            runButton.disabled = false;
            runButton.textContent = 'فعال'; // توجه: هنگام توقف، متن دکمه به "فعال" برمی‌گردد (نه "شروع")
        }
    }

    // توقف همه تایمرهای دیگر
    function stopAllOtherTimers(currentDisplayId, currentRunButton) {
        document.querySelectorAll('.task-timer-display').forEach(display => {
            const id = display.id;
            if (id && id !== currentDisplayId) {
                const taskBox = display.closest('.task-box');
                const runBtn = taskBox ? taskBox.querySelector('.run-task') : null;
                if (!timersData[id]?.isEnded) {
                    if (timersData[id] && timersData[id].intervalId) {
                        clearInterval(timersData[id].intervalId);
                        timersData[id].isRunning = false;
                        timersData[id].intervalId = null;
                    }
                    if (runBtn) {
                        runBtn.disabled = false;
                        runBtn.textContent = 'فعال';
                    }
                    if (currentActiveTimerId === id) currentActiveTimerId = null;
                }
            }
        });
    }

    // به‌روزرسانی نمایشگر بر اساس endTimestamp و همچنین به‌روزرسانی remainingSecondsCache
    function updateTimerDisplay(displayElement, endTimestamp, runButton) {
        const now = Date.now();
        let remainingSeconds = Math.max(0, Math.floor((endTimestamp - now) / 1000));
        displayElement.textContent = formatTime(remainingSeconds);

        // به‌روزرسانی کش مقدار باقیمانده
        const id = displayElement.id;
        if (timersData[id]) {
            timersData[id].remainingSecondsCache = remainingSeconds;
        }

        // اگر زمان تمام شد
        if (remainingSeconds <= 0) {
            if (timersData[id] && timersData[id].intervalId) {
                clearInterval(timersData[id].intervalId);
                timersData[id].intervalId = null;
                timersData[id].isRunning = false;
            }
            if (currentActiveTimerId === id) currentActiveTimerId = null;
            if (runButton && !timersData[id]?.isEnded) {
                runButton.disabled = false;
                runButton.textContent = 'فعال';
            }
            const box = displayElement.closest('.task-box');
            if (box) box.classList.remove('active-pulse');
            // تسک به پایان رسیده، می‌توان آن را end کرد
            if (!timersData[id]?.isEnded) {
                timersData[id] = { ...timersData[id], isEnded: true };
                // غیرفعال کردن دکمه‌ها در صورت وجود
                const taskBox = displayElement.closest('.task-box');
                if (taskBox) {
                    const endBtn = taskBox.querySelector('.end-task');
                    if (endBtn) endBtn.disabled = true;
                    if (runButton) runButton.disabled = true;
                }
            }
        }
    }

    // شروع تایمر (از مقدار باقیمانده کش)
    function startTimer(displayElement, totalSeconds, runButton) {
        const id = displayElement.id;
        const taskBox = displayElement.closest('.task-box');

        if (taskBox) {
            document.querySelectorAll('.task-box').forEach(box => box.classList.remove('active-pulse'));
            taskBox.classList.add('active-pulse');
        }

        // اگر تسک پایان یافته، هیچ کاری نکن
        if (timersData[id] && timersData[id].isEnded) return;

        // توقف همه تایمرهای دیگر (به جز خود این یکی)
        stopAllOtherTimers(id, runButton);

        // اگر همین تایمر در حال اجراست => آن را متوقف کن (رفتار toggle)
        if (timersData[id] && timersData[id].isRunning) {
            stopTimerAndResetButton(displayElement, runButton);
            return;
        }

        // دریافت مقدار باقیمانده (از کش یا مقدار اولیه)
        let remainingSec;
        if (timersData[id] && typeof timersData[id].remainingSecondsCache === 'number') {
            remainingSec = timersData[id].remainingSecondsCache;
        } else {
            remainingSec = totalSeconds;
        }

        // اگر قبلاً تمام شده (صفر یا کمتر) => شروع نکن
        if (remainingSec <= 0) return;

        const endTimestamp = Date.now() + (remainingSec * 1000);

        // پاک کردن تایمر قبلی اگر وجود دارد
        if (timersData[id] && timersData[id].intervalId) {
            clearInterval(timersData[id].intervalId);
        }

        const intervalId = setInterval(() => {
            // لازم است endTimestamp را از شیء دریافت کنیم چون ممکن است تغییر کرده باشد
            const currentEnd = timersData[id]?.endTimestamp || endTimestamp;
            updateTimerDisplay(displayElement, currentEnd, runButton);
        }, 500);

        timersData[id] = {
            intervalId: intervalId,
            endTimestamp: endTimestamp,
            remainingSecondsCache: remainingSec,
            isRunning: true,
            isEnded: false
        };
        currentActiveTimerId = id;

        if (runButton) {
            runButton.disabled = false;
            runButton.textContent = 'توقف';
        }

        // یک بار بلافاصله به‌روزرسانی کن
        updateTimerDisplay(displayElement, endTimestamp, runButton);
    }

    // پایان کامل تسک (بدون تغییر مقدار نمایش داده شده، فقط غیرفعال کردن)
    function endTask(displayElement, runButton, endButton) {
        const id = displayElement.id;
        const box = displayElement.closest('.task-box');
        if (box) box.classList.remove('active-pulse');

        // توقف تایمر اگر در حال اجراست
        if (timersData[id] && timersData[id].intervalId) {
            clearInterval(timersData[id].intervalId);
            timersData[id].isRunning = false;
            timersData[id].intervalId = null;
        }
        if (currentActiveTimerId === id) currentActiveTimerId = null;

        // علامت پایان - مقدار باقیمانده را همان چیزی که الان در کش است نگه می‌داریم
        timersData[id] = {
            ...timersData[id],
            isEnded: true,
            isRunning: false
        };

        // غیرفعال کردن دکمه‌ها
        if (runButton) {
            runButton.disabled = true;
            runButton.textContent = 'پایان یافته';
        }
        if (endButton) {
            endButton.disabled = true;
            endButton.textContent = 'پایان یافته';
        }
        // نمایشگر را تغییر نمی‌دهیم - همان مقداری که الان نشان می‌دهد باقی می‌ماند
    }

    // اتصال رویدادها به دکمه‌ها
    document.querySelectorAll('.run-task').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const taskBox = this.closest('.task-box');
            if (!taskBox) return;
            const timerDisplay = taskBox.querySelector('.task-timer-display');
            if (!timerDisplay) return;

            if (!timerDisplay.id) {
                timerDisplay.id = 'timer-' + Date.now() + '-' + Math.random();
            }

            const totalSeconds = parseInt(timerDisplay.dataset.seconds, 10);
            if (isNaN(totalSeconds)) return;

            const id = timerDisplay.id;
            if (timersData[id] && timersData[id].isEnded) return;

            startTimer(timerDisplay, totalSeconds, this);
        });
    });

    document.querySelectorAll('.end-task').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const taskBox = this.closest('.task-box');
            if (!taskBox) return;
            const timerDisplay = taskBox.querySelector('.task-timer-display');
            if (!timerDisplay) return;
            const runButton = taskBox.querySelector('.run-task');
            const id = timerDisplay.id;
            if (timersData[id] && timersData[id].isEnded) return;
            endTask(timerDisplay, runButton, this);
        });
    });
})();