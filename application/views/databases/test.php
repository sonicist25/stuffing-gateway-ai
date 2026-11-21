<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>http://stackoverflow.com/a/4480184/315935</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/themes/redmond/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.4.1/css/ui.jqgrid.css" />
    <style type="text/css">
        html, body { font-size: 75%; }
    </style>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.js"></script>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>
    <script type="text/javascript" src="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.4.1/js/i18n/grid.locale-en.js"></script>
    <script type="text/javascript">
        $.jgrid.no_legacy_api = true;
        $.jgrid.useJSON = true;
    </script>
    <script type="text/javascript" src="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.4.1/js/jquery.jqGrid.src.js"></script>
    <script type="text/javascript">
    //<![CDATA[
        /*global $ */
        /*jslint unparam: true */
        $(function () {
            "use strict";
            var countries = { "UsId": "US", "UkId": "UK" },
                statesOfUS = { "AlabamaId": "Alabama", "CaliforniaId": "California", "FloridaId": "Florida", "HawaiiId": "Hawaii" },
                statesOfUK = { "LondonId": "London", "OxfordId": "Oxford" },
                states = $.extend({}, statesOfUS, statesOfUK),
                allCountries = $.extend({"": "All"}, countries),
                allStates = $.extend({"": "All"}, states),
                // the next maps provide the states by ids to the contries
                statesOfCountry = {
                    "": states,
                    "UsId": statesOfUS,
                    "UkId": statesOfUK
                },
                mydata = 
                [
                    { id: "10", country: "UsId", state: "AlabamaId", name: "Louise Fletcher" },
                    { id: "20", country: "UsId", state: "FloridaId", name: "Jim Morrison" },
                    { id: "30", country: "UkId", state: "LondonId",  name: "Sherlock Holmes" },
                    { id: "40", country: "UkId", state: "OxfordId",  name: "Oscar Wilde" }
                ],
                lastSel = -1,
                grid = $("#list"),
                removeTheOptionAll = function (elem) {
                    // We use value:allCountries and value:allStates in the searchoptions.
                    // The option {"": "All"} neams "No filter" and should be displayed only
                    // in the searching toolbar and not in the searching dialog.
                    // So we use dataInit:removeTheOptionAll inside of searchoptions to remove
                    // the option {"": "All"} in case of the searching dialog
                    if (typeof elem === "object" && typeof elem.id === "string" && elem.id.substr(0, 3) !== "gs_") {
                        // we are NOT in the searching bar
                        $(elem).find("option[value=\"\"]").remove();
                    }
                },
                resetStatesValues = function () {
                    // reset the "value" property of the editoptions property to the initial value
                    grid.jqGrid("setColProp", "state", { editoptions: { value: states} });
                },
                setStateValues = function (countryId) {
                    // to have short list of options which corresponds to the country
                    // from the row we have to change "value" property of the editoptions property
                    // of the column temporary to new value
                    grid.jqGrid("setColProp", "state", { editoptions: { value: statesOfCountry[countryId]} });
                },
                changeStateSelect = function (countryId, countryElem) {
                    // build "state" options based on the selected "country" value
                    var stateId, stateSelect, parentWidth, $row,
                        $countryElem = $(countryElem),
                        sc = statesOfCountry[countryId],
                        isInSearchToolbar = $countryElem.parent().parent().parent().hasClass("ui-search-toolbar"),
                                          //$(countryElem).parent().parent().hasClass("ui-th-column")
                        newOptions = isInSearchToolbar ? "<option value=\"\">All</option>" : "";

                    for (stateId in sc) {
                        if (sc.hasOwnProperty(stateId)) {
                            newOptions += "<option role=\"option\" value=\"" + stateId + "\">" +
                                states[stateId] + "</option>";
                        }
                    }

                    setStateValues(countryId);

                    // populate the subset of contries
                    if (isInSearchToolbar) {
                        // searching toolbar
                        $row = $countryElem.closest("tr.ui-search-toolbar");
                        stateSelect = $row.find(">th.ui-th-column select#gs_state");
                        parentWidth = stateSelect.parent().width();
                        stateSelect.html(newOptions).css({width: parentWidth});
                    } else if ($countryElem.is(".FormElement")) {
                        // form editing
                        $countryElem.closest("form.FormGrid").find("select#state.FormElement").html(newOptions);
                    } else {
                        // inline editing
                        $row = $countryElem.closest("tr.jqgrow");
                        $("select#" + $.jgrid.jqID($row.attr("id")) + "_state").html(newOptions);
                    }
                },
                editGridRowOptions = {
                    recreateForm: true,
                    onclickPgButtons: function (whichButton, $form, rowid) {
                        var $row = $("#" + $.jgrid.jqID(rowid)), countryId;
                        if (whichButton === "next") {
                            $row = $row.next();
                        } else if (whichButton === "prev") {
                            $row = $row.prev();
                        }
                        if ($row.length > 0) {
                            countryId = grid.jqGrid("getCell", $row.attr("id"), "country");
                            changeStateSelect(countryId, $("#country")[0]);
                        }
                    },
                    onClose: function () {
                        resetStatesValues();
                    }
                };
        
            grid.jqGrid({
                data: mydata,
                datatype: "local",
                colModel: [
                    { name: "name", width: 200, editable: true },
                    { name: "country", width: 100, editable: true, formatter: "select", stype: "select", edittype: "select",
                        searchoptions: {
                            value: allCountries,
                            dataInit: removeTheOptionAll,
                            dataEvents: [
                                { type: "change", fn: function (e) { changeStateSelect($(e.target).val(), e.target); } },
                                { type: "keyup", fn: function (e) { $(e.target).trigger("change"); } }
                            ]
                        },
                        editoptions: {
                            value: countries,
                            dataInit: function (elem) { setStateValues($(elem).val()); },
                            dataEvents: [
                                { type: "change", fn: function (e) { changeStateSelect($(e.target).val(), e.target); } },
                                { type: "keyup", fn: function (e) { $(e.target).trigger("change"); } }
                            ]
                        }},
                    { name: "state", width: 100, formatter: "select", stype: "select",
                        editable: true, edittype: "select", editoptions: { value: states },
                        searchoptions: { value: allStates, dataInit: removeTheOptionAll } }
                ],
                onSelectRow: function (id) {
                    if (id && id !== lastSel) {
                        if (lastSel !== -1) {
                            $(this).jqGrid("restoreRow", lastSel);
                            resetStatesValues();
                        }
                        lastSel = id;
                    }
                },
                ondblClickRow: function (id) {
                    if (id && id !== lastSel) {
                        $(this).jqGrid("restoreRow", lastSel);
                        lastSel = id;
                    }
                    resetStatesValues();
                    $(this).jqGrid("editRow", id, {
                        keys: true,
                        aftersavefunc: function () {
                            resetStatesValues();
                        },
                        afterrestorefunc: function () {
                            resetStatesValues();
                        }
                    });
                    return;
                },
                editurl: "clientArray",
                sortname: "name",
                ignoreCase: true,
                height: "100%",
                viewrecords: true,
                rownumbers: true,
                sortorder: "desc",
                pager: "#pager",
                caption: "Demonstrate dependent selects (inline editing on double-click)"
            });
            grid.jqGrid("navGrid", "#pager", { del: false }, editGridRowOptions, editGridRowOptions);
            grid.jqGrid("filterToolbar", {stringResult: true, searchOnEnter: true, defaultSearch : "cn"});
        });
    //]]>
    </script>
</head>
<body>
    <table id="list"><tr><td></td></tr></table>
    <div id="pager"></div>
</body>
</html>