Number.prototype.pad = function(size) {
      var s = String(this);
      if(typeof(size) !== "number"){size = 2;}

      while (s.length < size) {s = "0" + s;}
      return s;
    }

String.prototype.replaceAll = function(oldValue, newValue) {
      return this.split(oldValue).join(newValue);
    }

moment.fn['isBeforeOrSame'] = function(value) {
    return !this.isAfter(value);
}

function formatUtcOffsetAsString(utcOffset) {
    var offset = '';
    if(utcOffset) {
        offset = formatMinutesAsString(utcOffset);
        if(utcOffset > 0)
            offset = '+' + offset;
    }

    return 'UTC' + offset;
}

function formatMinutesAsString(minutes) {
    var result = Math.floor(Math.abs(minutes) / 60).pad(2) + ":" + (minutes % 60).pad(2);
    if(minutes < 0)
        result = '-' + result;
    return result;
}

function formatTimezone(timezoneData) {

    return timezoneData.abbreviation + ' (' + formatUtcOffsetAsString(timezoneData.offsetInMinutes) + '): '+ timezoneData.identifier.split('_').join(' ');
}

function formatTimezoneCity(timezoneCity) {
    return timezoneCity.abbreviation + ' (' + formatUtcOffsetAsString(timezoneCity.offsetInMinutes) + '): ' + timezoneCity.city.split('_').join(' ');
}

function compareTimezones(a, b) {
    var result = a.offsetInMinutes - b.offsetInMinutes; // we want the negative offsets first
    if(result == 0) {
        if(a.abbreviation < b.abbreviation)
            return -1;
        else if(a.abbreviation > b.abbreviation)
            return 1;
        else {
            if(a.city < b.city)
                return -1;
            else if(a.city > b.city)
                return 1;
            else
                return 0;
        }
    }
    else {
        return result;
    }
}

function setTimezoneOfMoment(moment, timezone) {

    var a = moment.toArray(); // year,month,date,hours,minutes,seconds as an array

    moment = moment.tz(timezone);

    moment.year(a[0])
        .month(a[1])
        .date(a[2])
        .hours(a[3])
        .minutes(a[4])
        .seconds(a[5])
        .milliseconds(a[6]);

    return moment; // for chaining
};

function syncTimezonePicker(timezoneData) {
    if($('#timezone-countries').val() != timezoneData.countryCode) {
        $('#timezone-countries').val(timezoneData.countryCode).change();
        $('#timezone-cities').val(timezoneData.identifier);
    }
}

function setClientTimezone(timezoneIdentifier) {
    clientTimezone = timezoneIdentifier;
    rerenderCalendar();
}

var serverTimezone = '';
var clientTimezone = '';
var createdEventData = '';
var clientTimezoneData;

function rerenderCalendar() {
    var calendar = $('#calendar');
    var currentView = calendar.fullCalendar('getView');
    if(currentView && currentView.name) {
        calendar.fullCalendar('destroy');
        renderCalendar(currentView);
    }
    else
        renderCalendar();
}

function saveEvent() {

    var button = $('#save-event');

    if(button.hasClass('loading'))
        return;

    button.addClass('loading');
    var parameters = {
        start: createdEventData.startTimeServer.format(),
        end: createdEventData.endTimeServer.format(),
        emailAddress: createdEventData.emailAddress,
        skypeId: createdEventData.skypeId,
        name: createdEventData.name,
        title: createdEventData.title,
        clientTimezone: clientTimezone,
        startInClientTimezone: createdEventData.startTimeClient.format(),
        endInClientTimezone: createdEventData.endTimeClient.format()
    };
    $.post('save-event.php', parameters)
     .fail(function(data) {
        var body = 'Your message here%0D%0DUsed parameters:%0D'
            + JSON.stringify(parameters, null, 2).replaceAll('\n', '%0D') 
            + '%0D%0DError details:%0D' + JSON.stringify(data, null, 2).replaceAll('\n', '%0D');
        $('#send-error-mail').attr('href', $('#send-error-mail').attr('href') + '&body='+body);
        showMessage($('#schedule-error'));
     })
     .done(function() {
        deleteLocalEventSelection();
        reloadCalendarEvents();
        var message = $('#schedule-successful');
        showMessage(message, null, null, function() { 
            setTimeout(function() { hideMessage(message) }, 10000);
        });
     })
     .always(function(data) {
        console.log(data);
        button.removeClass('loading');
     });
}

function updateEventDetails(eventData) {
    $('#start-time-client').text(eventData.startTimeClient.format('lll'));
    $('#start-time-server').text(eventData.startTimeServer.format('lll'));
    $('#duration').text(eventData.duration);
    $('#save-event').removeClass('disabled');
    $('#event-details').show();
    $('#event-details-no-selection').hide();
}

function updateCreatedEvent(event) {
    createdEventData.startTimeClient = setTimezoneOfMoment(event.start.clone(), clientTimezone); 
    createdEventData.endTimeClient = setTimezoneOfMoment(event.end.clone(), clientTimezone);
    createdEventData.startTimeServer = createdEventData.startTimeClient.clone().tz(serverTimezone);
    createdEventData.endTimeServer = createdEventData.endTimeClient.clone().tz(serverTimezone);
    createdEventData.duration = formatMinutesAsString((createdEventData.endTimeServer - createdEventData.startTimeServer) / 1000 / 60);
    updateEventDetails(createdEventData); 
}

function deleteLocalEventSelection() {
    $('#calendar').fullCalendar('removeEvents', 'local-event');
    $('#event-details').hide();
    $('#event-details-no-selection').show();
    $('#save-event').addClass('disabled');
    createdEventData = '';
}

function reloadCalendarEvents() {
    $('#calendar').fullCalendar('refetchEvents');
}

function getOverlappingEvents(startTimeClient, endTimeClient) {
    return $('#calendar').fullCalendar('clientEvents', function(event) {
        if(event.id == 'local-event')
            return false;
        var isStartInside = event.start.isBeforeOrSame(startTimeClient) && startTimeClient.isBefore(event.end);
        var isStartBefore = startTimeClient.isBeforeOrSame(event.start);
        var isEndInside = event.start.isBefore(endTimeClient) && endTimeClient.isBeforeOrSame(event.end);
        var isEndAfter = event.end.isBeforeOrSame(endTimeClient);
        return (
            (isStartInside && isEndAfter) ||
            (isStartInside && isEndInside) ||
            (isStartBefore && isEndInside) ||
            (isStartBefore && isEndAfter));
    });
}

function overlapsExistingEvents(startTimeClient, endTimeClient) {
    return getOverlappingEvents(startTimeClient, endTimeClient).length > 0;
}

function showFreeSpotWarning() {
    var message = $('#warning-free-spot');
    showMessage(message, null, null, function() { 
        setTimeout(function() { hideMessage(message) }, 5000);
    });
}

function hideFreeSpotWarning() {
    hideMessage($('#warning-free-spot'));
}

function renderCalendar(currentView) {

    var calendar = $('#calendar');
    var view = 'agendaWeek';
    var defaultDate = $.fullCalendar.moment();
    if(currentView) {
        view = currentView.name;
        defaultDate = currentView.start;
    }

    var selectedDateRange = $('#calendar-selected-range');

    var eventChanged = function( event, revertFunc, jsEvent, ui, view ) {
        var startTimeClient = setTimezoneOfMoment(event.start, clientTimezone);
        var endTimeClient = setTimezoneOfMoment(event.end, clientTimezone);
        if(overlapsExistingEvents(startTimeClient, endTimeClient)) {
            showFreeSpotWarning();
            revertFunc();
            return;
        }

        hideFreeSpotWarning();

        updateCreatedEvent(event);
    };

    calendar.fullCalendar({
        loading: function(bool) {
            if(bool)
                calendar.dimmer('show', { closable: false });
            else
                calendar.dimmer('hide', { closable: false });
        },
        viewRender: function(view) {
            selectedDateRange.text(view.title);
        },
        header: false,
        slotDuration: '00:30',
        weekNumbers: true,
        editable: true,
        selectable: true,
        selectHelper: true,
        defaultView: view,
        defaultDate: defaultDate,
        allDaySlot: false,
        timezone: clientTimezone,
        select: function(start, end) {

            var startTimeClient = setTimezoneOfMoment(start, clientTimezone);
            var endTimeClient = setTimezoneOfMoment(end, clientTimezone);
            var startTimeServer = startTimeClient.clone().tz(serverTimezone);
            var endTimeServer = endTimeClient.clone().tz(serverTimezone);
            var duration = formatMinutesAsString((endTimeServer - startTimeServer) / 1000 / 60);

            if(overlapsExistingEvents(startTimeClient, endTimeClient)) {
                showFreeSpotWarning();
                calendar.fullCalendar('unselect');
                return;
            }

            hideFreeSpotWarning();

            var name = $('#name').val();
            var emailAddress = $('#email').val();
            var skypeId = $('#skype-id').val();

            createdEventData = {
                id: 'local-event',
                title: 'Call: Daniel Hilgarth / ' + name,
                start: startTimeClient.format(),
                end: endTimeClient.format(),
                name: name,
                emailAddress: emailAddress,
                skypeId: skypeId,
                startTimeClient: startTimeClient,
                endTimeClient: endTimeClient,
                startTimeServer: startTimeServer,
                endTimeServer: endTimeServer,
                duration: duration
            };

            calendar.fullCalendar('removeEvents', 'local-event');
            calendar.fullCalendar('renderEvent', createdEventData, true);
            calendar.fullCalendar('unselect');

            updateEventDetails(createdEventData);
        },
        eventDrop: eventChanged,
        eventResize: eventChanged,
        events: {
            url: 'get-events.php',
            data: function () {
                return {
                    emailAddress: $('#email').val(),
                    name: $('#name').val()
                };
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus);
                console.log(errorThrown.stack);
                console.log(jqXHR.responseText);
                $('#script-warning').show();
                calendar.hide();
            }
        },
    });
};

function initializeTimezonePicker() {

    $.getJSON('get-timezones.php', function(data) { 
        var timezoneCountries = $('#timezone-countries');
        var timezoneCities = $('#timezone-cities');

        var initializeTimezoneCities = function(countryCode) {
            timezoneCities.empty();
            $.each(data[countryCode].cities.sort(compareTimezones), function(idx, cityData) {
                var option = $("<option/>", { value: cityData.identifier, text: formatTimezoneCity(cityData) });
                if(clientTimezoneData && clientTimezoneData.identifier == cityData.identifier) {
                    option.prop('selected', true);
                }
                timezoneCities.append(option);
            });

            setClientTimezone(timezoneCities.val());
        };

        $.each(data, function(countryCode, countryData) {
            var option = $("<option/>", { value: countryCode, text: countryData.countryName });
            if(clientTimezoneData && clientTimezoneData.countryCode == countryCode) {
                option.prop('selected', true);
                initializeTimezoneCities(countryCode);
            }
            timezoneCountries.append(option);
        });

        timezoneCountries.change(function() {
            var country = $(this).val();
            initializeTimezoneCities(country);
        });

        timezoneCities.change(function() {
            setClientTimezone($(this).val());
        });
    });
}

function initializeTimezoneSubsystem() {

    var clientTimezone = jstz.determine().name();

    initializeTimezonePicker();

    $.getJSON('get-timezones.php?server', function(data) {
        $('#server-timezone').text(formatTimezone(data));
        serverTimezone = data.identifier;
    });

    $.getJSON('get-timezones.php', { client: clientTimezone }, function(data) {
        clientTimezoneData = data;
        setClientTimezone(data.identifier);
        syncTimezonePicker(data);
    });
}

function initializeCalendarHeader() {
    var calendar = $('#calendar');
    $('#calendar-prev').click(function() {
        calendar.fullCalendar('prev');
    });
    $('#calendar-next').click(function() {
        calendar.fullCalendar('next');
    });
    $('#calendar-today').click(function() {
        calendar.fullCalendar('today');
    });
    $('#calendar-week').click(function() {
        calendar.fullCalendar('changeView', 'agendaWeek');
    });
    $('#calendar-day').click(function() {
        calendar.fullCalendar('changeView', 'agendaDay');
    });
}

function showMessage(message, durationFadeIn, durationSlideDown, complete) {
    if(message.length == 0)
        return;
    else if(message.length > 1) {
        $.each(message, function(_, m) { showMessage($(m), durationFadeIn, durationSlideDown, complete); });
        return;
    }

    if (typeof durationFadeIn === "undefined" || durationFadeIn === null)
        durationFadeIn = 1500;
    if (typeof durationSlideDown === "undefined" || durationSlideDown === null)
        durationSlideDown = 400;

    if(message.css('display') !== 'none')
        return;

    var placeholder = message.prev('.message-placeholder');
    placeholder.slideDown({
        duration: durationSlideDown,
        complete: function () {
            placeholder.remove();
            message.removeClass('hidden'); // workaround for https://github.com/Semantic-Org/Semantic-UI/issues/763
            message.transition('fade in', {
                duration: durationFadeIn,
                onShow: function() { 
                    message.removeClass('visible'); // workaround for https://github.com/Semantic-Org/Semantic-UI/issues/763
                    if(complete != null)
                        complete();
                }
            });
        }
    });
}

function hideMessage(message, durationFadeOut, durationSlideUp, complete) {
    if(message.length == 0)
        return;
    else if(message.length > 1) {
        $.each(message, function(_, m) { hideMessage($(m), durationFadeOut, durationSlideUp, complete); });
        return;
    }
    if (typeof durationFadeOut === "undefined" || durationFadeOut === null)
        durationFadeOut = 1500;
    if (typeof durationSlideUp === "undefined" || durationSlideUp === null)
        durationSlideUp = 400;

    if(message.css('display') === 'none')
        return;

    var placeholder = $('<div />').height(message.outerHeight(false)).css('margin', message.css('margin')).addClass('message-placeholder');
    message.transition('fade out', {
        duration: durationFadeOut,
        onHide: function () {
            message.before(placeholder);
            placeholder.slideUp(durationSlideUp, function() {
                if(complete != null)
                    complete();
            });
        }
    });    
}

$(document).ready(function() {

    $.site('disable debug'); 

    var validationRules = {
        name : {
            identifier: 'name',
            rules : [
                { 
                    type : 'empty',
                    prompt: 'Please enter your name'
                }
            ]
        },
        skypeId : {
            identifier: 'skype-id',
            rules : [
                { 
                    type : 'empty',
                    prompt: 'Please enter your Skype ID'
                }
            ]
        },
        email : {
            identifier: 'email',
            rules : [
                { 
                    type : 'empty',
                    prompt: 'Please enter your email address'
                },
                {
                    type   : 'email',
                    prompt : 'Please enter a valid email address'
                }
            ]
        }
    };
    $('#contact-data .ui.form').form(validationRules, { inline: true, on: 'blur' });

    initializeTimezoneSubsystem();
    initializeCalendarHeader();

    $('#save-event').click(saveEvent);

    $('#contact-data').submit(function(event) { 
        event.preventDefault();
        hideMessage($('.hideable.message'), 1, 1);
        $('#page1').hide();
        $('#page2').show();
        $('#calendar').fullCalendar('render');
        $('#step-page1').removeClass('active');
        $('#step-page2').addClass('active').removeClass('disabled');
        return false;
    });

    $('#step-page1').click(function() {
        $('#page1').show();
        $('#page2').hide();
        $('#step-page1').addClass('active');
        $('#step-page2').removeClass('active').addClass('disabled');
    });

    $('.message .close').click(function() {
        hideMessage($(this).closest('.message'));
    });
});
