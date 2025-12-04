// Notification System
class NotificationSystem {
    constructor() {
        this.lastNotificationCount = 0;
        this.checkInterval = 30000; // 30 seconds
        this.init();
    }

    init() {
        this.checkNotifications();
        setInterval(() => this.checkNotifications(), this.checkInterval);
        this.setupEventListeners();
    }

    async checkNotifications() {
        try {
            const response = await fetch('api/get_notifications.php');
            const data = await response.json();

            if (data.success) {
                const unreadCount = data.unread_count;

                // Update badge
                this.updateBadge(unreadCount);

                // Play sound if new notifications
                if (unreadCount > this.lastNotificationCount) {
                    this.playNotificationSound();
                }

                this.lastNotificationCount = unreadCount;

                // Update dropdown list
                if (data.notifications) {
                    this.updateNotificationList(data.notifications);
                }
            }
        } catch (error) {
            console.error('Error checking notifications:', error);
        }
    }

    updateBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }

    updateNotificationList(notifications) {
        const container = document.getElementById('notificationList');
        if (!container) return;

        if (notifications.length === 0) {
            container.innerHTML = `
                <div class="p-4 text-center text-gray-500">
                    <i class="fas fa-bell-slash text-3xl mb-2"></i>
                    <p>لا توجد إشعارات</p>
                </div>
            `;
            return;
        }

        container.innerHTML = notifications.map(notif => `
            <div class="notification-item ${notif.is_read == 0 ? 'unread' : ''}" data-id="${notif.id}">
                <div class="flex items-start gap-3 p-3 hover:bg-gray-50 cursor-pointer" onclick="notificationSystem.markAsRead(${notif.id}, '${notif.action_url || '#'}')">
                    <div class="flex-shrink-0">
                        <i class="fas ${this.getIconForType(notif.type)} text-blue-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">${notif.title}</p>
                        <p class="text-sm text-gray-600">${notif.message}</p>
                        <p class="text-xs text-gray-400 mt-1">${this.formatTime(notif.created_at)}</p>
                    </div>
                    ${notif.is_read == 0 ? '<div class="flex-shrink-0"><span class="w-2 h-2 bg-blue-600 rounded-full"></span></div>' : ''}
                </div>
            </div>
        `).join('');
    }

    getIconForType(type) {
        const icons = {
            'appointment': 'fa-calendar-check',
            'reminder': 'fa-bell',
            'system': 'fa-info-circle'
        };
        return icons[type] || 'fa-bell';
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // seconds

        if (diff < 60) return 'الآن';
        if (diff < 3600) return `منذ ${Math.floor(diff / 60)} دقيقة`;
        if (diff < 86400) return `منذ ${Math.floor(diff / 3600)} ساعة`;
        return `منذ ${Math.floor(diff / 86400)} يوم`;
    }

    async markAsRead(notificationId, actionUrl) {
        try {
            await fetch('api/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            });

            // Refresh notifications
            this.checkNotifications();

            // Navigate if URL provided
            if (actionUrl && actionUrl !== '#') {
                window.location.href = actionUrl;
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            await fetch('api/mark_all_notifications_read.php', {
                method: 'POST'
            });

            // Refresh notifications
            this.checkNotifications();
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    }

    playNotificationSound() {
        // Create a simple beep sound using Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (error) {
            console.error('Error playing notification sound:', error);
        }
    }

    setupEventListeners() {
        // Toggle dropdown
        const bellIcon = document.getElementById('notificationBell');
        const dropdown = document.getElementById('notificationDropdown');

        if (bellIcon && dropdown) {
            bellIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target) && !bellIcon.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }

        // Mark all as read button
        const markAllBtn = document.getElementById('markAllAsRead');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', () => this.markAllAsRead());
        }
    }
}

// Initialize notification system when DOM is ready
let notificationSystem;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        notificationSystem = new NotificationSystem();
    });
} else {
    notificationSystem = new NotificationSystem();
}
