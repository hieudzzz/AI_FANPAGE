/**
 * AI Fanpage Toast Notification System
 */
const AIF_Toast = {
    init: function() {
        if (!document.getElementById('aif-toast-container')) {
            const container = document.createElement('div');
            container.id = 'aif-toast-container';
            document.body.appendChild(container);

            // Inject Styles
            const style = document.createElement('style');
            style.textContent = `
                #aif-toast-container {
                    position: fixed;
                    top: 30px;
                    right: 30px;
                    z-index: 999999;
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                    pointer-events: none;
                }
                .aif-toast {
                    background: #fff;
                    color: #1e293b;
                    padding: 16px 24px;
                    border-radius: 12px;
                    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
                    display: flex;
                    align-items: center;
                    gap: 14px;
                    min-width: 320px;
                    max-width: 450px;
                    pointer-events: auto;
                    transform: translateX(120%);
                    transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                    border-left: 6px solid #6366f1;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                }
                .aif-toast.show {
                    transform: translateX(0);
                }
                .aif-toast-success { border-left-color: #10b981; }
                .aif-toast-error { border-left-color: #ef4444; }
                .aif-toast-info { border-left-color: #3b82f6; }
                .aif-toast-warning { border-left-color: #f59e0b; }
                .aif-toast-icon {
                    font-size: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }
                .aif-toast-icon .dashicons {
                    font-size: 24px;
                    width: 24px;
                    height: 24px;
                }
                .aif-toast-content {
                    flex: 1;
                    font-size: 14px;
                    font-weight: 600;
                    line-height: 1.5;
                }
                .aif-toast-close {
                    cursor: pointer;
                    opacity: 0.5;
                    font-size: 20px;
                    line-height: 1;
                    padding: 4px;
                    transition: opacity 0.2s;
                }
                .aif-toast-close:hover { opacity: 1; }
            `;
            document.head.appendChild(style);
        }
    },

    show: function(message, type = 'success') {
        this.init();
        
        const container = document.getElementById('aif-toast-container');
        const toast = document.createElement('div');
        toast.className = `aif-toast aif-toast-${type}`;
        
        // Icon
        let icon = '';
        if (type === 'success') icon = '<span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:inline-block;"></span>';
        else if (type === 'error') icon = '<span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span>';
        else if (type === 'warning') icon = '<span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block;"></span>';
        else icon = '<span style="width:10px;height:10px;border-radius:50%;background:#3b82f6;display:inline-block;"></span>';

        toast.innerHTML = `
            <div class="aif-toast-icon">${icon}</div>
            <div class="aif-toast-content">${message}</div>
            <div class="aif-toast-close">&times;</div>
        `;

        container.appendChild(toast);

        // Animate In
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        // Close Logic
        const closeBtn = toast.querySelector('.aif-toast-close');
        closeBtn.addEventListener('click', () => {
             this.remove(toast);
        });

        // Auto Remove
        setTimeout(() => {
            this.remove(toast);
        }, 4000);
    },

    remove: function(toast) {
        toast.classList.remove('show');
        toast.addEventListener('transitionend', () => {
            if(toast.parentElement) toast.remove();
        });
    }
};

// Expose to global if needed, or just standard window usage
window.AIF_Toast = AIF_Toast;
