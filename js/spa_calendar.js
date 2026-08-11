(function() {
    'use strict';

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function(character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
        });
    }

    function parseDate(value) {
        var parts = value.split('-').map(Number);
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function dateKey(date) {
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' +
            String(date.getDate()).padStart(2, '0');
    }

    function formatTime(value) {
        var parts = String(value || '').split(':');
        var hour = Number(parts[0]);
        var minute = parts[1] || '00';
        var suffix = hour >= 12 ? 'pm' : 'am';
        return (hour % 12 || 12) + ':' + minute + ' ' + suffix;
    }

    function formatDate(date) {
        return date.toLocaleDateString(undefined, {month: 'long', day: 'numeric', year: 'numeric'});
    }

    function seasonClass(season) {
        return 'spa-season-' + String(season || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }

    function seasonStyle(season) {
        var styles = {
            'Advent': 'background-color:#4169e1;border-left-color:#1d3f9c;color:#fff;',
            'Christmas': 'background-color:#d4af37;border-left-color:#927516;color:#241d00;',
            'Epiphany': 'background-color:#2e8b57;border-left-color:#1c5b39;color:#fff;',
            'Lent': 'background-color:#6a4c93;border-left-color:#493366;color:#fff;',
            'Holy Week': 'background-color:#8b0000;border-left-color:#5d0000;color:#fff;',
            'Easter': 'background-color:#f1d36b;border-left-color:#b18c18;color:#241d00;',
            'Season of Pentecost': 'background-color:#2e8b57;border-left-color:#1c5b39;color:#fff;',
            'Pentecost': 'background-color:#2e8b57;border-left-color:#1c5b39;color:#fff;'
        };
        return styles[season] || '';
    }

    function specialDayClass(specialDay) {
        return 'spa-special-day-' + String(specialDay || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }

    function specialDayStyle(specialDay) {
        var styles = {
            'Baptism of Our Lord': 'background-color:#f5f1df;border-left-color:#b18c18;color:#241d00;',
            'Transfiguration of Our Lord': 'background-color:#f5f1df;border-left-color:#b18c18;color:#241d00;',
            'Ash Wednesday': 'background-color:#222;border-left-color:#000;color:#fff;',
            'Palm Sunday': 'background-color:#6a4c93;border-left-color:#493366;color:#fff;',
            'Maundy Thursday': 'background-color:#f5f1df;border-left-color:#b18c18;color:#241d00;',
            'Good Friday': 'background-color:#222;border-left-color:#000;color:#fff;',
            'Easter Vigil': 'background-color:#f5f1df;border-left-color:#b18c18;color:#241d00;',
            'Ascension Day': 'background-color:#f5f1df;border-left-color:#b18c18;color:#241d00;',
            'Day of Pentecost': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'Holy Trinity': 'background-color:#f5f1df;border-left-color:#b18c18;color:#241d00;',
            'Reformation Day': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'All Saints\' Day': 'background-color:#f5f1df;border-left-color:#b18c18;color:#241d00;',
            'St. Stephen, Martyr': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'St. John, Apostle and Evangelist': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'Holy Innocents, Martyrs': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'Confession of St. Peter': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'Conversion of St. Paul': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'Presentation of Our Lord': 'background-color:#f5f1df;border-left-color:#b18c18;color:#241d00;',
            'St. Joseph, Guardian of Jesus': 'background-color:#f5f1df;border-left-color:#b18c18;color:#241d00;',
            'Annunciation of Our Lord': 'background-color:#f5f1df;border-left-color:#b18c18;color:#241d00;',
            'St. Mark, Evangelist': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'St. Philip and St. James, Apostles': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'St. Barnabas, Apostle': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'St. Peter and St. Paul, Apostles': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'St. Mary Magdalene': 'background-color:#f5f1df;border-left-color:#b18c18;color:#241d00;',
            'St. James of Jerusalem': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'St. Mary, Mother of Our Lord': 'background-color:#f5f1df;border-left-color:#b18c18;color:#241d00;',
            'St. Matthew, Evangelist': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'St. Michael and All Angels': 'background-color:#f5f1df;border-left-color:#b18c18;color:#241d00;',
            'St. Luke, Evangelist': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'St. Simon and St. Jude, Apostles': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'St. Andrew, Apostle': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'St. Thomas, Apostle': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;',
            'St. Matthias, Apostle': 'background-color:#c62828;border-left-color:#8d1d1d;color:#fff;'
        };
        return styles[specialDay] || '';
    }

    function initCalendar(container) {
        var events;
        try {
            events = JSON.parse(container.getAttribute('data-events') || '[]');
        } catch (error) {
            container.textContent = 'Unable to load calendar events.';
            return;
        }

        var today = parseDate(spaCalendar.today);
        var state = {view: spaCalendar.defaultView, date: today};

        function eventsOn(date) {
            return events.filter(function(event) { return event.event_date === dateKey(date); });
        }

        function eventMarkup(event) {
            var colorStyle = specialDayStyle(event.special_day) || seasonStyle(event.season);
            return '<button type="button" class="spa-calendar-event ' + seasonClass(event.season) + ' ' +
                specialDayClass(event.special_day) + '" style="' + colorStyle + '" data-event-id="' + Number(event.id) + '">' +
                escapeHtml(event.name) + '</button>';
        }

        function summary(event) {
            var churchDay = event.church_day || event.special_day || event.season;
            return '<strong>' + escapeHtml(event.name) + '</strong><div>' +
                formatTime(event.start_time) + ' - ' + formatTime(event.end_time) + '</div>' +
                (churchDay ? '<div><strong>Day of the Church Year:</strong> ' + escapeHtml(churchDay) + '</div>' : '') +
                (event.location ? '<div>' + escapeHtml(event.location) + '</div>' : '') +
                (event.description ? '<p>' + escapeHtml(event.description) + '</p>' : '');
        }

        function showPopup(event, anchor) {
            var popup = container.querySelector('.spa-calendar-popup');
            popup.innerHTML = summary(event) + (spaCalendar.isLoggedIn
                ? '<button type="button" class="spa-calendar-details-button" data-event-id="' + Number(event.id) +
                    '">View teams and volunteers</button>' : '');
            popup.hidden = false;
            var anchorRect = anchor.getBoundingClientRect();
            var parentRect = container.getBoundingClientRect();
            popup.style.left = Math.max(0, anchorRect.left - parentRect.left) + 'px';
            popup.style.top = (anchorRect.bottom - parentRect.top + 8) + 'px';
        }

        function renderHeader() {
            var title = state.view === 'month'
                ? state.date.toLocaleDateString(undefined, {month: 'long', year: 'numeric'})
                : formatDate(state.date);
            return '<div class="spa-calendar-toolbar"><div class="spa-calendar-navigation">' +
                '<button type="button" data-calendar-action="previous" aria-label="Previous">‹</button>' +
                '<button type="button" data-calendar-action="today">Today</button>' +
                '<button type="button" data-calendar-action="next" aria-label="Next">›</button>' +
                '</div><h3>' + escapeHtml(title) + '</h3><div class="spa-calendar-views">' +
                ['month', 'week', 'agenda'].map(function(view) {
                    return '<button type="button" class="' + (state.view === view ? 'active' : '') +
                        '" data-calendar-view="' + view + '">' + view.charAt(0).toUpperCase() + view.slice(1) + '</button>';
                }).join('') + '</div></div>';
        }

        function renderMonth() {
            var first = new Date(state.date.getFullYear(), state.date.getMonth(), 1);
            var start = new Date(first);
            start.setDate(first.getDate() - first.getDay());
            var html = '<div class="spa-calendar-month-grid"><div class="spa-calendar-weekdays">' +
                ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(function(day) {
                    return '<div>' + day + '</div>';
                }).join('') + '</div><div class="spa-calendar-month-days">';
            for (var index = 0; index < 42; index++) {
                var day = new Date(start);
                day.setDate(start.getDate() + index);
                var classes = day.getMonth() === state.date.getMonth() ? '' : ' outside-month';
                if (dateKey(day) === dateKey(today)) classes += ' today';
                html += '<div class="spa-calendar-day' + classes + '"><span class="spa-calendar-day-number">' +
                    day.getDate() + '</span>';
                eventsOn(day).forEach(function(event) { html += eventMarkup(event); });
                html += '</div>';
            }
            return html + '</div></div>';
        }

        function renderWeek() {
            var start = new Date(state.date);
            start.setDate(state.date.getDate() - state.date.getDay());
            var html = '<div class="spa-calendar-week-grid">';
            for (var index = 0; index < 7; index++) {
                var day = new Date(start);
                day.setDate(start.getDate() + index);
                html += '<div class="spa-calendar-week-day' + (dateKey(day) === dateKey(today) ? ' today' : '') + '">' +
                    '<h4>' + day.toLocaleDateString(undefined, {weekday: 'short', month: 'short', day: 'numeric'}) + '</h4>';
                eventsOn(day).forEach(function(event) {
                    html += '<div class="spa-calendar-week-event"><span>' + formatTime(event.start_time) +
                        '</span>' + eventMarkup(event) + '</div>';
                });
                html += '</div>';
            }
            return html + '</div>';
        }

        function renderAgenda() {
            var upcoming = events.filter(function(event) { return parseDate(event.event_date) >= state.date; }).slice(0, 20);
            if (!upcoming.length) return '<p class="spa-calendar-empty">' + escapeHtml(spaCalendar.strings.noEvents) + '</p>';
            return '<div class="spa-calendar-agenda">' + upcoming.map(function(event) {
                return '<article><time>' + escapeHtml(formatDate(parseDate(event.event_date))) + '</time><div>' +
                    eventMarkup(event) + '<span>' + formatTime(event.start_time) + ' - ' + formatTime(event.end_time) +
                    '</span></div></article>';
            }).join('') + '</div>';
        }

        function render() {
            container.innerHTML = renderHeader() + '<div class="spa-calendar-content">' +
                (state.view === 'month' ? renderMonth() : state.view === 'week' ? renderWeek() : renderAgenda()) +
                '</div><div class="spa-calendar-popup" hidden></div>';
        }

        function loadDetails(eventId) {
            var popup = container.querySelector('.spa-calendar-popup');
            popup.innerHTML = '<p>' + escapeHtml(spaCalendar.strings.loading) + '</p>';
            var body = new URLSearchParams({action: 'spa_calendar_event_details', event_id: eventId, nonce: spaCalendar.nonce});
            fetch(spaCalendar.ajaxUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: body.toString()
            }).then(function(response) { return response.json(); }).then(function(response) {
                if (!response.success) throw new Error(response.data && response.data.message ? response.data.message : spaCalendar.strings.error);
                popup.innerHTML = response.data.html;
            }).catch(function(error) { popup.innerHTML = '<p>' + escapeHtml(error.message) + '</p>'; });
        }

        container.addEventListener('click', function(event) {
            var viewButton = event.target.closest('[data-calendar-view]');
            var action = event.target.closest('[data-calendar-action]');
            var eventButton = event.target.closest('.spa-calendar-event');
            var detailsButton = event.target.closest('.spa-calendar-details-button');
            if (viewButton) {
                state.view = viewButton.getAttribute('data-calendar-view');
                render();
            } else if (action) {
                var direction = action.getAttribute('data-calendar-action');
                if (direction === 'today') state.date = new Date(today);
                else {
                    var amount = direction === 'previous' ? -1 : 1;
                    state.date = new Date(state.date);
                    if (state.view === 'month') state.date.setMonth(state.date.getMonth() + amount);
                    else state.date.setDate(state.date.getDate() + (state.view === 'week' ? amount * 7 : amount * 20));
                }
                render();
            } else if (detailsButton) {
                loadDetails(detailsButton.getAttribute('data-event-id'));
            } else if (eventButton) {
                var selected = events.find(function(item) {
                    return Number(item.id) === Number(eventButton.getAttribute('data-event-id'));
                });
                if (selected) {
                    showPopup(selected, eventButton);
                    if (spaCalendar.isLoggedIn) loadDetails(selected.id);
                }
            } else if (!event.target.closest('.spa-calendar-popup')) {
                container.querySelector('.spa-calendar-popup').hidden = true;
            }
        });

        container.addEventListener('mouseover', function(event) {
            var eventButton = event.target.closest('.spa-calendar-event');
            if (!eventButton || !container.contains(eventButton)) return;
            var selected = events.find(function(item) {
                return Number(item.id) === Number(eventButton.getAttribute('data-event-id'));
            });
            if (selected) showPopup(selected, eventButton);
        });

        render();
    }

    document.querySelectorAll('.spa-public-calendar').forEach(initCalendar);
}());
