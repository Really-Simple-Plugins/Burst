jQuery(document).ready(function ($) {
    'use strict';
    /**
     * Start and stop experiment from the dashboard statistics overview
     */

    $(document).on('click', '.burst-statistics-action', function(){

        var type = $(this).data('experiment_action');
        var experiment_id = $('select[name=burst_selected_experiment_id]').val();
        burstDisableStartStopBtns();

        $.ajax({
            type: "POST",
            url: burst.ajaxurl,
            dataType: 'json',
            data: ({
                action: 'burst_experiment_action',
                type: type,
                experiment_id: experiment_id,
                token: burst.token
            }),
            success: function (response) {
                if (response.success) {
                    burstEnableStartStopBtns();
                    $('.burst-experiment-start').hide();
                    $('.burst-experiment-stop').hide();
                    if (type==='start') {
                        $('.burst-experiment-stop').show();
                    } else {
                        $('.burst-experiment-start').show();
                    }

                }

            }
        });
    });

    function burstEnableStartStopBtns(){
        $('.burst-experiment-start button').removeAttr('disabled');
        $('.burst-experiment-stop button').removeAttr('disabled');
    }

    function burstDisableStartStopBtns(){
        $('.burst-experiment-start button').attr('disabled', true);
        $('.burst-experiment-stop button').attr('disabled', true);
    }

    function burstInitChartJS() {
        burstDisableStartStopBtns();

        var XscaleLabelDisplay = false;
        var YscaleLabelDisplay = true;
        var titleDisplay = false;
        var legend = true;
        var config = {
            type: 'line',
            data: {
                labels: ['...', '...', '...', '...', '...', '...', '...'],
                datasets: [{
                    label: '...',
                    backgroundColor: 'rgb(255, 99, 132)',
                    borderColor: 'rgb(255, 99, 132)',
                    data: [
                        0, 0, 0, 0, 0, 0, 0,
                    ],
                    fill: false,
                }

                ]
            },
            options: {
                legend:{
                    display:legend,
                },
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: titleDisplay,
                    text: 'Select an experiment'
                },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    xAxes: [{
                        display: XscaleLabelDisplay,
                        scaleLabel: {
                            display: true,
                            labelString: 'Date'
                        }
                    }],
                    yAxes: [{
                        display: YscaleLabelDisplay,
                        scaleLabel: {
                            display: true,
                            labelString: 'Count'
                        },
                        ticks: {
                            beginAtZero: true,
                            min: 0,
                            max: 1,
                            stepSize: 5
                        }
                    }]
                }
            }
        };

        var ctx = document.getElementsByClassName('burst-chartjs-stats');
        window.conversionGraph = new Chart(ctx, config);
        var date_start = localStorage.getItem('burst_range_start');
        var date_end = localStorage.getItem('burst_range_end');

        var experiment_id = $('select[name=burst_selected_experiment_id]').val();
        if (experiment_id>0) {
            $.ajax({
                type: "get",
                dataType: "json",
                url: burst.ajaxurl,
                data: {
                    action: "burst_get_experiment_statistics",
                    experiment_id: experiment_id,
                    date_start: date_start,
                    date_end: date_end,
                },
                success: function (response) {
                    if (response.success == true) {
                        var i = 0;
                        response.data.datasets.forEach(function (dataset) {
                            if (config.data.datasets.hasOwnProperty(i)) {
                                config.data.datasets[i] = dataset;
                            } else {
                                var newDataset = dataset;
                                config.data.datasets.push(newDataset);
                            }

                            i++;
                        });
                        config.data.labels = response.data.labels;
                        config.options.title.text = response.title;
                        config.options.scales.yAxes[0].ticks.max = parseInt(response.data.max);
                        window.conversionGraph.update();
                        burstEnableStartStopBtns();
                    } else {
                        alert("Your experiment data could not be loaded")
                    }
                }
            })
        }
    }


    var strToday= burstLocalizeString('Today');
    var strYesterday = burstLocalizeString('Yesterday');
    var strLast7= burstLocalizeString('Last 7 days');
    var strLast30= burstLocalizeString('Last 30 days');
    var strThisMonth= burstLocalizeString('This Month');
    var strLastMonth= burstLocalizeString('Last Month');

    var ranges = {}
    ranges[strToday] = [todayStart, todayEnd];
    ranges[strYesterday] = [yesterdayStart, yesterdayEnd];
    ranges[strLast7] = [lastWeekStart, lastWeekEnd];
    ranges[strLast30] = [moment().subtract(31, 'days'), yesterdayEnd];
    ranges[strThisMonth] = [moment().startOf('month'), moment().endOf('month')];
    ranges[strLastMonth] = [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')];

    var unixStart = localStorage.getItem('burst_range_start');
    var unixEnd = localStorage.getItem('burst_range_end');

    if (unixStart === null || unixEnd === null ) {
        unixStart = moment().endOf('day').subtract(1, 'week').unix();
        unixEnd = moment().endOf('day').unix();
        localStorage.setItem('burst_range_start', unixStart);
        localStorage.setItem('burst_range_end', unixEnd);
    }

    unixStart = parseInt(unixStart);
    unixEnd = parseInt(unixEnd);
    burstUpdateDate(moment.unix(unixStart), moment.unix(unixEnd));
    burstInitChartJS();

    function burstUpdateDate(start, end) {
        $('.burst-date-container span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        localStorage.setItem('burst_range_start', start.add( moment().utcOffset(), 'm' ).unix());
        localStorage.setItem('burst_range_end', end.add( moment().utcOffset(), 'm' ).unix());
    }
    var todayStart = moment().endOf('day').subtract(1, 'days').add(1, 'minutes');
    var todayEnd = moment().endOf('day');
    var yesterdayStart = moment().endOf('day').subtract(2, 'days').add(1, 'minutes');

    var yesterdayEnd = moment().endOf('day').subtract(1, 'days');
    var lastWeekStart = moment().endOf('day').subtract(8, 'days').add(1, 'minutes');
    var lastWeekEnd = moment().endOf('day').subtract(1, 'days');
    $('.burst-date-container.burst-date-range').daterangepicker(
        {
            ranges: ranges,
            "locale": {
                "format": burstLocalizeString("date_format","burst"),
                "separator": " - ",
                "applyLabel": burstLocalizeString("Apply","burst"),
                "cancelLabel": burstLocalizeString("Cancel","burst"),
                "fromLabel": burstLocalizeString("From","burst"),
                "toLabel": burstLocalizeString("To","burst"),
                "customRangeLabel": burstLocalizeString("Custom","burst"),
                "weekLabel": burstLocalizeString("W","burst"),
                "daysOfWeek": [
                    burstLocalizeString("Mo","burst"),
                    burstLocalizeString("Tu","burst"),
                    burstLocalizeString("We","burst"),
                    burstLocalizeString("Th","burst"),
                    burstLocalizeString("Fr","burst"),
                    burstLocalizeString("Sa","burst"),
                    burstLocalizeString("Su","burst"),
                ],
                "monthNames": [
                    burstLocalizeString("January"),
                    burstLocalizeString("February"),
                    burstLocalizeString("March"),
                    burstLocalizeString("April"),
                    burstLocalizeString("May"),
                    burstLocalizeString("June"),
                    burstLocalizeString("July"),
                    burstLocalizeString("August"),
                    burstLocalizeString("September"),
                    burstLocalizeString("October"),
                    burstLocalizeString("November"),
                    burstLocalizeString("December")
                ],
                "firstDay": 1
            },
            "alwaysShowCalendars": true,
            startDate: moment.unix(unixStart),
            endDate: moment.unix(unixEnd),
            "opens": "left",
        }, function (start, end, label) {
            burstUpdateDate(start, end);
            burstInitChartJS();
        });

    $(document).on('change', 'select[name=burst_selected_experiment_id]', function(){
        burstInitChartJS();
        burstLoadGridBlocks();
    });

    burstLoadGridBlocks();
    function burstLoadGridBlocks(){
        var experiment_id = $('select[name=burst_selected_experiment_id]').val();
        var date_start = localStorage.getItem('burst_range_start');
        var date_end = localStorage.getItem('burst_range_end');

        $('.burst-load-ajax').each(function(){
            var gridContainer = $(this);
            var type = gridContainer.data('table_type');
            $.ajax({
                type: "get",
                dataType: "json",
                url: burst.ajaxurl,
                data: {
                    action: "burst_load_grid_block",
                    experiment_id: experiment_id,
                    date_start: date_start,
                    date_end: date_end,
                    type:type,
                },
                success: function (response) {
                    console.log(response);
                    if (response.success) {
                        gridContainer.find('.item-content').html(response.html);
                    }
                }
            })
        });
    }


});

