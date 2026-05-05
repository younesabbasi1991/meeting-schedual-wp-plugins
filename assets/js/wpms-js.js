
document.querySelectorAll('.wpms-read-more-container').forEach(container => {
    const content = container.querySelector('.wpms-read-more-content');
    const btn = container.querySelector('.wpms-toggle-btn');

    // اگر محتوا از 100px کمتر است، دکمه را مخفی کن
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
    // تبدیل ثانیه به HH:MM:SS با پشتیبانی از منفی
    function formatTime(seconds) {
        let isNegative = seconds < 0;
        let absSeconds = Math.abs(seconds);
        let hours = Math.floor(absSeconds / 3600);
        let minutes = Math.floor((absSeconds % 3600) / 60);
        let secs = absSeconds % 60;
        let formatted = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        return isNegative ? '-' + formatted : formatted;
    }

    // ذخیره اطلاعات تایمرها برای هر المان (کلید = id نمایشگر)
    let timersData = {};
    // نگهداری شناسه تایمر فعال فعلی (برای اطمینان از یک تایمر همزمان)
    let currentActiveTimerId = null;

    // توقف کامل تایمر (بدون تغییر دکمه شروع/توقف - برای استفاده داخلی)
    function forceStopTimer(displayElement) {
        const id = displayElement.id;
        if (timersData[id] && timersData[id].intervalId) {
            clearInterval(timersData[id].intervalId);
            timersData[id].isRunning = false;
            timersData[id].intervalId = null;
        }
        if (currentActiveTimerId === id) {
            currentActiveTimerId = null;
        }
    }

    // توقف تایمر و ریست کردن دکمه مربوطه به حالت اولیه (فعال)
    function stopTimerAndResetButton(displayElement, runButton) {
        const id = displayElement.id;
        if (timersData[id] && timersData[id].intervalId) {
            clearInterval(timersData[id].intervalId);
            timersData[id].isRunning = false;
            timersData[id].intervalId = null;
        }
        if (currentActiveTimerId === id) {
            currentActiveTimerId = null;
        }
        if (runButton && !timersData[id]?.isEnded) {
            runButton.disabled = false;
            runButton.textContent = 'فعال';
        }
    }

    // توقف همه تایمرهای دیگر (به جز تسک فعلی که می‌خواهیم شروع شود)
    function stopAllOtherTimers(currentDisplayId, currentRunButton) {
        document.querySelectorAll('.task-timer-display').forEach(display => {
            const id = display.id;
            if (id && id !== currentDisplayId) {
                const taskBox = display.closest('.task-box');
                const runBtn = taskBox ? taskBox.querySelector('.run-task') : null;
                // اگر تسک پایان نیافته باشد، تایمر را متوقف و دکمه را ریست کن
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
                    if (currentActiveTimerId === id) {
                        currentActiveTimerId = null;
                    }
                }
            }
        });
    }

    // شروع تایمر (با قابلیت شروع از روی مقدار باقیمانده)
    function startTimer(displayElement, totalSeconds, runButton) {
        const id = displayElement.id;
        // اگر تسک پایان یافته است، اجازه شروع نده
        if (timersData[id] && timersData[id].isEnded) return;

        // قبل از شروع، همه تایمرهای دیگر را متوقف کن
        stopAllOtherTimers(id, runButton);

        // اگر تایمر فعلی در حال اجراست، آن را متوقف کن (رفتار toggle)
        if (timersData[id] && timersData[id].isRunning) {
            stopTimerAndResetButton(displayElement, runButton);
            return;
        }

        // مقدار باقیمانده: اگر قبلاً ذخیره شده از آن استفاده کن، وگرنه از کل ثانیه اولیه
        let remaining;
        if (timersData[id] && typeof timersData[id].remainingSeconds === 'number') {
            remaining = timersData[id].remainingSeconds;
        } else {
            remaining = totalSeconds;
        }

        const intervalId = setInterval(() => {
            remaining--;
            displayElement.textContent = formatTime(remaining);
            timersData[id].remainingSeconds = remaining;
        }, 1000);

        timersData[id] = {
            intervalId: intervalId,
            remainingSeconds: remaining,
            isRunning: true,
            isEnded: timersData[id]?.isEnded || false
        };
        currentActiveTimerId = id;

        if (runButton) {
            runButton.disabled = false;
            runButton.textContent = 'توقف';
        }
    }

    // پایان کامل تسک (فریز و غیرفعال کردن دکمه‌ها)
    function endTask(displayElement, runButton, endButton) {
        const id = displayElement.id;
        // توقف تایمر اگر در حال اجراست
        if (timersData[id] && timersData[id].intervalId) {
            clearInterval(timersData[id].intervalId);
            timersData[id].isRunning = false;
            timersData[id].intervalId = null;
        }
        if (currentActiveTimerId === id) {
            currentActiveTimerId = null;
        }
        // علامت پایان یافتن
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
    }

    // بارگذاری اولیه: اتصال رویدادها به دکمه‌ها
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

            // اگر تسک پایان یافته، هیچ کاری انجام نده
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
            // اگر تسک قبلاً پایان یافته، دوباره کاری نکن
            const id = timerDisplay.id;
            if (timersData[id] && timersData[id].isEnded) return;

            endTask(timerDisplay, runButton, this);
        });
    });
})();