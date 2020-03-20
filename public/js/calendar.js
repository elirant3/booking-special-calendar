function addZero(i) {
    if (i < 10) {
        i = "0" + i;
    }
    return i;
}

let calendar = {
    cache: {},
    date: null,
    calendarObject: null,
    calendarPointer: null,
    customDateValue: '',
    month: '',
    year: '',
    day: '',
    days: 0,
    currentShownDate: '',
    markup: '',
    hoursPayload: {},
    next: {
        period: undefined,
        click: function (that) {
            return function () {
                that.next.period = this.getAttribute('data-period');
                if (that.next.period === 'month')
                    that.date = new Date(that.currentShownDate.getFullYear(), that.currentShownDate.getMonth() + 1, 1);
                else if (that.next.period === 'year')
                    that.date = new Date(that.currentShownDate.getFullYear() + 1, that.currentShownDate.getMonth(), 1);

                that.customDateValue = '';

                if (false) {
                    console.log('from Cache');
                    that.calendarPointer.innerHTML = that.cache[Date.parse(that.date.toString())];
                    that.setListeners();
                } else {
                    that.init();
                }

                /*if (that.cache[Date.parse(that.date.getFullYear() + '-' + that.date.getMonth())]) {
                    console.log('from Cache');
                    that.calendarPointer.innerHTML = that.cache[Date.parse(that.date.getFullYear() + '-' + that.date.getMonth())];
                    that.setListeners();
                } else {
                    console.log('Generated');
                    that.init();
                }*/
            }
        },
        markup: function (period) {
            return '<button data-period="' + period + '" class="s_btn s_btn_next s_calendar_next">Next ' + period + '</button>';
        },
    },
    prev: {
        period: undefined,
        click: function (that) {
            return function () {
                that.prev.period = this.getAttribute('data-period');
                if (that.prev.period === 'month')
                    that.date = new Date(that.currentShownDate.getFullYear(), that.currentShownDate.getMonth() - 1, 1);
                else if (that.prev.period === 'year')
                    that.date = new Date(that.currentShownDate.getFullYear() - 1, that.currentShownDate.getMonth(), 1);

                that.customDateValue = '';
                that.init();
            }
        },
        markup: function (period) {
            return '<button data-period="' + period + '" class="s_btn s_btn_prev s_calendar_prev">Prev ' + period + '</button>';
        },
    },
    init: function () {
        if (this.date === null) this.date = new Date();
        this.markup = '<div class="actionLine">';
        this.markup += '<p>' + this.next.markup('month') + '' + this.prev.markup('month') + '</p>';
        this.markup += '<p>' + this.next.markup('year') + '' + this.prev.markup('year') + '</p>';
        this.markup += '<p><input type="date" value="' + this.customDateValue + '" name="customDate" class="customDate"></p>';
        this.markup += '</div>';
        this.setHours.get.call(this)
    },
    genForm: {
        hour_id: undefined,
        form: '',
        closeBtn: function () {
            this.elem = document.createElement('span');
            this.elem.classList.add('closeForm');
            this.elem.innerHTML = '&#10799;';
            this.createListener('click', [this.elem], this.genForm.closeForm);
            return this.elem;
        },
        fields: {
            fName: {
                elem: '',
            },
            lName: {
                elem: '',
            },
            phone: {
                elem: '',
            },
            csrf: {
                elem: '',
            },
            email: {
                elem: '',
            },
        },
        submitMeetingHour: function (e, c) {
            return function (i) {
                i.preventDefault();
                let btn = this.querySelector('button[type=submit]'),
                    that = this;
                btn.setAttribute('disabled', '');

                that.cacheText = btn.innerText;
                btn.innerText = CALENDAR.savingText;
                let xhr = new XMLHttpRequest();

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            let response = JSON.parse(this.response);
                            if (response.success) {
                                e.calendarPointer.innerHTML = '<div class="successMessage"><p>' + response.success + '</p></div>';
                                c.remove();
                                iziToast.success({
                                    message: response.success,
                                });
                            } else if (response.error) {
                                iziToast.error({
                                    title: 'Error',
                                    message: response.error,
                                });
                            }
                        } catch (e) {
                            console.error(e.stack);
                        } finally {
                            btn.removeAttribute('disabled');
                            btn.innerText = that.cacheText;
                        }
                    }
                }

                xhr.onerror = function () {
                    btn.removeAttribute('disabled');
                    btn.innerText = that.cacheText;
                    throw new Error('Network Error');
                };

                xhr.open('POST', CALENDAR.ajaxurl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send('action=' + 'bslc_addNewMeetingRequest' +
                    '&lName=' + this.querySelector('input[name=lName]').value +
                    '&fName=' + this.querySelector('input[name=fName]').value +
                    '&email=' + this.querySelector('input[name=email]').value +
                    '&submitMeetingNonce=' + this.querySelector('input[name=submitMeetingNonce]').value +
                    '&hour_id=' + e.genForm.hour_id +
                    '&phone=' + this.querySelector('input[name=phone]').value
                );
            }
        },
        createElements: function (obj) {
            let tmp,
                container,
                ezer,
                submit,
                title;

            ezer = document.createElement('div');
            container = document.createElement('div');
            submit = document.createElement('button');
            container.classList.add('s_calendar_form_wrap');
            submit.classList.add('saveMeetingBtn', 's_cal_btn');
            submit.type = 'submit';
            submit.innerText = CALENDAR.submitText;

            this.form = document.createElement('form');
            this.form.method = 'post';
            this.form.classList = 'saveMeetingHour';

            this.form.appendChild(this.closeBtn.call(obj));
            for (let i in this.fields) {
                if (this.fields.hasOwnProperty(i)) {
                    tmp = document.createElement('input');
                    switch (i) {
                        case 'lName':
                            title = CALENDAR.lName;
                            tmp.type = 'text';
                            tmp.name = i;
                            tmp.id = i;
                            break;
                        case 'fName':
                            title = CALENDAR.fName;
                            tmp.type = 'text';
                            tmp.name = i;
                            tmp.id = i;
                            break;
                        case 'email':
                            title = CALENDAR.email;
                            tmp.type = 'email';
                            tmp.name = i;
                            tmp.id = i;
                            break;
                        case 'phone':
                            title = CALENDAR.phone;
                            tmp.type = 'tel';
                            tmp.name = i;
                            tmp.id = i;
                            break;
                        case 'csrf':
                            tmp.type = 'hidden';
                            tmp.name = 'submitMeetingNonce';
                            tmp.id = 'submitMeetingNonce';
                            tmp.value = CALENDAR.submitMeetingNonce;
                            break;
                        default:
                            title = 'Unknown';
                            tmp.type = 'text';
                            tmp.name = 'test';
                            tmp.id = 'test';
                            break;
                    }

                    let formGroup = this.addWrap(tmp, title);
                    title = '';
                    this.form.appendChild(formGroup);
                }
            }

            this.form.appendChild(submit);
            this.form.addEventListener('submit', this.submitMeetingHour(obj, container));

            ezer.appendChild(this.form);
            container.appendChild(ezer);
            return container;
        },
        get: function () {
            return this.genForm.createElements.apply(this.genForm, [this]);
        },
        addWrap(tmp, title) {
            let formGroup,
                label;

            formGroup = document.createElement('div');
            if (tmp.type === 'hidden') {
                return tmp;
            } else {
                label = document.createElement('label');
                label.setAttribute('for', tmp.id);
                label.appendChild(document.createTextNode(title))
                label.appendChild(tmp);
                label.classList.add('label-control');
                formGroup.appendChild(label);
            }

            formGroup.classList.add('form-group');
            return formGroup;
        },
        closeForm() {
            try {
                document.querySelector('.s_calendar_form_wrap').remove();
            } catch (e) {
                console.log(e.stack);
            }
        }
    },
    setHours: {
        get: function () {
            let xhr = new XMLHttpRequest(),
                that = this;

            let trigger = function () {
                return function () {
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            let response = JSON.parse(xhr.response);
                            if (response['success']) {
                                that.hoursPayload = response['success'];
                            }
                            that.resetCalendar();
                            that.setListeners();
                        }
                    };

                    xhr.onerror = function () {
                        throw new Error('Network Error');
                    };

                    xhr.open('POST', CALENDAR.ajaxurl, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.send("action=bslc_getCalendarHours&date=" + Date.parse(that.date));
                }
            }();

            trigger();
        },
    },
    setCustomDate: function (that) {
        return function () {
            that.date = new Date(this.value);
            that.customDateValue = this.value;
            that.init();
        }
    },
    resetCalendar: function () {
        let y = this,
            g_date = this.date,
            placeholder = this.calendarPointer,
            c_month = g_date.getMonth() + 1;

        y.daysInMonth(c_month, g_date.getFullYear(), g_date.getDate());
        this.markup += '<ul>';
        this.markup = y.getCalendar();
        this.markup += '</ul>';
        this.markup += '<div class="clearfix"></div>';

        this.cache[Date.parse(g_date.getFullYear() + '-' + c_month)] = this.markup;
        placeholder.innerHTML = this.markup;
    },
    setListeners: function () {
        try {
            let that = this,
                nextBtns = that.calendarPointer.querySelectorAll('.s_calendar_next'),
                prevBtns = that.calendarPointer.querySelectorAll('.s_calendar_prev'),
                days = that.calendarPointer.querySelectorAll('.s_calendar > ul > li.day'),
                closeHoursBox = that.calendarPointer.querySelectorAll('.closeHoursBox'),
                hour = that.calendarPointer.querySelectorAll('.s_calendar > ul > li.day .hoursBox .hour');

            if (days.length > 0) {
                let helper = function (e) {
                    return function (i) {
                        let box = this.querySelector('.hoursBox');
                        if (box) {
                            that.calendarPointer.querySelectorAll('.hoursBox.active').forEach(function (obj, key) {
                                obj.classList.remove('active');
                            });

                            this.querySelector('.hoursBox').classList.add('active');
                        }
                    }
                };

                for (let u = 0; u < days.length; u++) {
                    days[u].addEventListener('click', helper())
                }
            }

            that.calendarPointer.querySelectorAll('.customDate').forEach(function (obj) {
                obj.addEventListener('change', that.setCustomDate(that));
            });

            if (nextBtns.length > 0) {
                for (let x = 0; x < nextBtns.length; x++) {
                    nextBtns[x].addEventListener('click', that.next.click(that))
                }
            }
            if (prevBtns.length > 0) {
                for (let x = 0; x < prevBtns.length; x++) {
                    prevBtns[x].addEventListener('click', that.prev.click(that))
                }
            }

            if (hour.length > 0) {
                let helper = function (e) {
                    return function (i) {
                        if (that.calendarPointer) {
                            if (this.classList.contains('approved') || this.classList.contains('unavailable')) {
                                iziToast.error({
                                    title: 'Sorry',
                                    message: 'Unavailable hour.',
                                });
                                return true;
                            } else {
                                if (document.body.querySelectorAll('.saveMeetingHour')) {
                                    document.body.querySelectorAll('.saveMeetingHour').forEach(function (obj) {
                                        obj.remove();
                                    })
                                }

                                that.genForm.hour_id = this.getAttribute('data-hourid');
                                document.body.appendChild(that.genForm.get.call(that));
                            }
                        }
                    }
                }
                for (let r = 0; r < hour.length; r++) {
                    hour[r].addEventListener('click', helper())
                }
            }

            if (closeHoursBox.length > 0) {
                this.createListener('click', closeHoursBox, this.closeHourBox);
            }

        } catch (e) {
            console.error(e.message);
            console.error(e.stack);
        }
    },
    closeHourBox: function () {
        try {
            this.parentNode.classList.remove('active');
            console.log(this)
        } catch (e) {
            console.error(e.message);
        }
    },
    createListener: function (type, elements, callback) {
        try {
            for (let e = 0; e < elements.length; e++) {
                let helper = function (e) {
                    return function (i) {
                        i.stopPropagation();
                        callback.call(this);
                    }
                }
                elements[e].addEventListener(type, helper(e));
            }
        } catch (e) {
            console.error(e.message);
        }
    },
    daysInMonth: function (month, year, day) {
        this.currentShownDate = new Date(year, month, 0);
        this.year = year;
        this.month = month;
        this.day = new Date().getDate();
        this.days = this.currentShownDate.getDate();
    },
    getCalendar: function () {
        if (this.days > 0) {
            this.markup += '<li class="actionLine">';
            this.markup += '<h1>' + this.year + '/' + this.month + '</h1>';
            this.markup += '</li>'
            let payload = '',
                that = this,
                status;

            for (let x = 1; x <= this.days; x++) {
                try {
                    payload = this.hoursPayload[this.year][this.month][x];
                    if (addZero(x) === addZero(this.day)) {
                        that.markup += '<li class="day current"><span>' + x + '</span>';
                    } else {
                        that.markup += '<li class="day"><span>' + x + '</span>';
                    }

                    if (undefined !== payload && payload.length > 0) {
                        that.markup += '<span class="openHoursAnchor">click to open</span>';
                        that.markup += '<div class="hoursBox">';
                        that.markup += '<span class="closeHoursBox">&#10799;</span>';
                        payload.forEach(function (obj, key) {
                            status = (obj.meta_data) ? (obj.meta_data === 'approved') ? 'unavailable approved' : 'unavailable' : '';
                            obj.h_from = new Date(obj.h_from);
                            obj.h_to = new Date(obj.h_to);
                            that.markup += '<p data-hourid="' + obj.id + '" class="hour ' + status + '">' + obj.h_from.getHours() + ':' + addZero(obj.h_from.getMinutes()) + ':' + addZero(obj.h_from.getSeconds())
                                + ' - ' + obj.h_to.getHours() + ':' + addZero(obj.h_to.getMinutes()) + ':' + addZero(obj.h_to.getSeconds()) + '</p>';
                        });
                        that.markup += '</div>';
                        that.markup += '</li>';
                    }
                } catch (e) {
                    that.markup += '<li class="day">' + x + '</li>';
                }
            }
            if (that.days < 31) {
                for (let y = this.days; y < 31; y++) {
                    that.markup += '<li class="day placeHolder"></li>';
                }
            }
        }
        return this.markup;
    }
};

function SpecialCalendar(object_id) {
    this.object_id = object_id;
    this.calendarObject = null;

    this.generateBoard = function () {
        let tmp = document.querySelector(this.object_id);

        if (this.object_id === '.s_calendar')
            throw new Error('Object id illegal. ' + this.object_id + ' reserved word.');

        if (tmp) {
            tmp.classList.add('s_calendar');
            this.calendarObject = Object.create(calendar);
            this.calendarObject.calendarPointer = tmp;
            calendar.init.call(this.calendarObject);
        }
    };

    this.generateBoard.call(this);
}

document.addEventListener("DOMContentLoaded", function (event) {
    try {
        new SpecialCalendar('.calendar1');
    } catch (e) {
        console.error(e.message);
        console.error(e.stack);
    }
});
