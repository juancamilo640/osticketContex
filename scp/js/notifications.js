/**
 * osTicket Real-Time Notification System
 * Polls for ticket events and shows toast + browser notifications
 */
(function($) {
    'use strict';
    console.warn('[OstNotifications] SCRIPT FILE LOADED AND PARSED!');

    var OstNotifications = {
        lastPollTime: 0,
        seenIds: {},
        pollInterval: 15000, // 15 seconds
        timer: null,
        notifPermission: 'default',
        soundEnabled: true,

        init: function() {
            console.log('[OstNotifications] Initialize notification system');
            this.createContainer();
            this.requestBrowserPermission();
            this.createBellIcon();
            this.poll();
            this.timer = setInterval(this.poll.bind(this), this.pollInterval);
            setInterval(this.keepAlive.bind(this), 300000); // 5 min
        },

        createContainer: function() {
            if ($('#ost-toast-container').length) return;
            $('body').append(
                '<div id="ost-toast-container"></div>'
            );
        },

        createBellIcon: function() {
            var $info = $('#info');
            if (!$info.length || $('#ost-notif-bell').length) return;
            var bell = '<span id="ost-notif-bell" title="Notificaciones" style="cursor:pointer;position:relative;margin-right:8px;font-size:16px;">' +
                '<i class="icon-bell-alt"></i>' +
                '<span id="ost-notif-badge" style="display:none;position:absolute;top:-8px;right:-8px;' +
                'background:#e74c3c;color:#fff;border-radius:50%;min-width:16px;height:16px;font-size:10px;' +
                'line-height:16px;text-align:center;padding:0 3px;">0</span></span>';
            $info.prepend(bell);
            $('#ost-notif-bell').on('click', function() {
                $('#ost-notif-badge').fadeOut();
            });
        },

        requestBrowserPermission: function() {
            if ('Notification' in window) {
                if (Notification.permission === 'granted') {
                    this.notifPermission = 'granted';
                } else if (Notification.permission !== 'denied') {
                    try {
                        var promise = Notification.requestPermission(function(p) {
                            OstNotifications.notifPermission = p;
                        });
                        if (promise) {
                            promise.then(function(p) { OstNotifications.notifPermission = p; }).catch(function(){});
                        }
                    } catch(e) {}
                }
            }
        },

        keepAlive: function() {
            var basePath = window.ostNotifBasePath || 'ajax.php/';
            $.ajax({ url: basePath + 'ajax.php/notifications/count', type: 'GET', dataType: 'json' });
        },

        poll: function() {
            var self = this;
            var basePath = window.ostNotifBasePath || '';
            console.log('[OstNotifications] Polling endpoint: ' + basePath + 'ajax.php/notifications/poll?since=' + self.lastPollTime);
            $.ajax({
                url: basePath + 'ajax.php/notifications/poll?since=' + self.lastPollTime,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (!data || !data.notifications) {
                        console.log('[OstNotifications] Polled, no data returned', data);
                        return;
                    }
                    console.log('[OstNotifications] Poll successful. Count: ' + data.notifications.length, data);
                    self.lastPollTime = data.server_time || Math.floor(Date.now() / 1000);
                    var newCount = 0;
                    var items = data.notifications.slice().reverse();
                    for (var i = 0; i < items.length; i++) {
                        var n = items[i];
                        var key = n.id + '_' + n.unix_ts;
                        if (self.seenIds[key]) continue;
                        self.seenIds[key] = true;
                        newCount++;
                        console.log('[OstNotifications] Showing notification for ticket: ' + n.ticket_number);
                        self.showToast(n);
                        self.showBrowserNotification(n);
                    }
                    if (newCount > 0) self.updateBadge(newCount);
                },
                error: function(xhr, status, error) { 
                    console.error('[OstNotifications] Poll failed: ', status, error, xhr.responseText);
                }
            });
        },

        getEventInfo: function(evt) {
            var map = {
                'created':     { icon: 'icon-plus-sign',      label: 'Nuevo Ticket',        color: '#2ecc71' },
                'message':     { icon: 'icon-comment',         label: 'Nuevo Mensaje',       color: '#3498db' },
                'reply':       { icon: 'icon-reply',           label: 'Nueva Respuesta',     color: '#9b59b6' },
                'closed':      { icon: 'icon-ok-circle',       label: 'Ticket Cerrado',      color: '#e67e22' },
                'reopened':    { icon: 'icon-undo',            label: 'Ticket Reabierto',    color: '#e74c3c' },
                'assigned':    { icon: 'icon-hand-right',      label: 'Ticket Asignado',     color: '#1abc9c' },
                'transferred': { icon: 'icon-share-alt',       label: 'Ticket Transferido',  color: '#f39c12' },
                'overdue':     { icon: 'icon-warning-sign',    label: 'Ticket Vencido',      color: '#c0392b' },
                'edited':      { icon: 'icon-pencil',          label: 'Ticket Editado',      color: '#7f8c8d' }
            };
            return map[evt] || { icon: 'icon-info-sign', label: evt, color: '#95a5a6' };
        },

        showToast: function(n) {
            var info = this.getEventInfo(n.event);
            var subject = n.ticket_subject || '';
            if (subject.length > 50) subject = subject.substring(0, 50) + '...';
            var ticketUrl = 'tickets.php?id=' + n.ticket_id;
            var timeStr = this.timeAgo(n.unix_ts);

            var html =
                '<div class="ost-toast" data-ticket="' + n.ticket_id + '" style="border-left-color:' + info.color + '">' +
                  '<div class="ost-toast-close">&times;</div>' +
                  '<div class="ost-toast-header">' +
                    '<i class="' + info.icon + '" style="color:' + info.color + '"></i> ' +
                    '<strong>' + info.label + '</strong>' +
                    '<span class="ost-toast-time">' + timeStr + '</span>' +
                  '</div>' +
                  '<div class="ost-toast-body">' +
                    '<div class="ost-toast-ticket">#' + n.ticket_number + '</div>' +
                    '<div class="ost-toast-subject">' + this.escHtml(subject) + '</div>' +
                    '<div class="ost-toast-user">' + this.escHtml(n.username || '') + '</div>' +
                  '</div>' +
                '</div>';

            var $toast = $(html);
            $('#ost-toast-container').prepend($toast);
            setTimeout(function() { $toast.addClass('ost-toast-show'); }, 50);

            $toast.on('click', '.ost-toast-close', function(e) {
                e.stopPropagation();
                $toast.removeClass('ost-toast-show');
                setTimeout(function() { $toast.remove(); }, 400);
            });
            $toast.on('click', function() {
                window.location.href = ticketUrl;
            });

            // Auto-dismiss after 8 seconds
            setTimeout(function() {
                $toast.removeClass('ost-toast-show');
                setTimeout(function() { $toast.remove(); }, 400);
            }, 8000);
        },

        showBrowserNotification: function(n) {
            if (this.notifPermission !== 'granted') return;
            if (document.hasFocus()) return; // Only when tab not focused
            var info = this.getEventInfo(n.event);
            try {
                var notif = new Notification(info.label + ' - #' + n.ticket_number, {
                    body: (n.ticket_subject || '') + '\n' + (n.username || ''),
                    icon: '../images/oscar-favicon-32x32.png',
                    tag: 'ost-' + n.id
                });
                notif.onclick = function() {
                    window.focus();
                    window.location.href = 'tickets.php?id=' + n.ticket_id;
                    notif.close();
                };
                setTimeout(function() { notif.close(); }, 10000);
            } catch(e) {}
        },

        updateBadge: function(count) {
            var $badge = $('#ost-notif-badge');
            if (!$badge.length) return;
            var cur = parseInt($badge.text()) || 0;
            $badge.text(cur + count).fadeIn();
        },

        timeAgo: function(ts) {
            var diff = Math.floor(Date.now() / 1000) - ts;
            if (diff < 60) return 'ahora';
            if (diff < 3600) return Math.floor(diff / 60) + 'm';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h';
            return Math.floor(diff / 86400) + 'd';
        },

        escHtml: function(s) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(s));
            return d.innerHTML;
        }
    };

    $(function() {
        OstNotifications.init();
    });

})(jQuery);
