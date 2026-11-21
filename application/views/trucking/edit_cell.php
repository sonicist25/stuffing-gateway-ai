
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>http://stackoverflow.com/questions/6047211/jqgrid-set-focus-on-first-cell-inside-a-row-not-with-cell-id</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/themes/redmond/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.0.0/css/ui.jqgrid.css" />
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
    <script type="text/javascript" src="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.0.0/js/i18n/grid.locale-en.js"></script>
    <script type="text/javascript" src="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.0.0/js/jquery.jqGrid.min.js"></script>

    <script type="text/javascript">
    //<![CDATA[
        $(document).ready(function () {
            var mydata = [
                    {id:"1", invdate:"2007-10-01",name:"test",  note:"note",  amount:"200.00",tax:"10.00",total:"210.00",status:1},
                    {id:"2", invdate:"2007-10-02",name:"test2", note:"note2", amount:"300.00",tax:"20.00",total:"320.00",status:2},
                    {id:"3", invdate:"2007-09-01",name:"test3", note:"note3", amount:"400.00",tax:"30.00",total:"430.00",status:3},
                    {id:"4", invdate:"2007-10-04",name:"test",  note:"note4", amount:"200.00",tax:"10.00",total:"210.00",status:1},
                    {id:"5", invdate:"2007-10-05",name:"test5", note:"note5", amount:"300.00",tax:"20.00",total:"320.00",status:2},
                    {id:"6", invdate:"2007-09-06",name:"test",  note:"note6", amount:"400.00",tax:"30.00",total:"430.00",status:3},
                    {id:"7", invdate:"2007-10-04",name:"test7", note:"note7", amount:"200.00",tax:"10.00",total:"210.00",status:1},
                    {id:"8", invdate:"2007-10-03",name:"test8", note:"note8", amount:"300.00",tax:"20.00",total:"320.00",status:2},
                    {id:"9", invdate:"2007-09-01",name:"test",  note:"note9", amount:"400.00",tax:"30.00",total:"430.00",status:3},
                    {id:"10",invdate:"2007-09-08",name:"test10",note:"note10",amount:"500.00",tax:"30.00",total:"530.00",status:1},
                    {id:"11",invdate:"2007-09-08",name:"test11",note:"note11",amount:"500.00",tax:"30.00",total:"530.00",status:2}
                ],
                grid = $("#list"),
                getColumnIndexByName = function(grid,columnName) {
                    var cm = grid.jqGrid('getGridParam','colModel'),i=0,l=cm.length;
                    for (; i<l; i++) {
                        if (cm[i].name === columnName) {
                            return i; // return the index
                        }
                    }
                    return -1;
                },
                getTextFromCell = function(cellNode) {
                    if (cellNode.childNodes[0].nodeName.toUpperCase() === "SELECT") {
                        var selOptions = $("option:selected", cellNode);
                        if (selOptions.length>0) {
                            return selOptions.text();
                        }
                    }
                    return cellNode.childNodes[0].nodeName.toUpperCase() === "INPUT"?
                           cellNode.childNodes[0].value:
                           cellNode.textContent || cellNode.innerText;
                },
                decodingMap = {Confirmed:"1", Open:"2", Rejected:"3"},
                calculateTotal = function() {
                    var totalAmount = 0,
                        iAmount=getColumnIndexByName(grid,'amount'),
                        iStatus=getColumnIndexByName(grid,'status');
                    var i=0, rows = grid[0].rows, rowsCount = rows.length, row, status;

                    for(;i<rowsCount;i++) {
                        row = rows[i];
                        if (row.className.indexOf('jqgrow') !== -1) {
                            status = getTextFromCell(row.cells[iStatus]);
                            if (decodingMap[status] === "1") { // "Confirmed"
                                totalAmount += Number(getTextFromCell(row.cells[iAmount]));
                            }
                        }
                    }

                    grid.jqGrid('footerData','set',{name:'TOTAL Confirmed',amount:totalAmount});
                };

            grid.jqGrid({
                datatype: "local",
                data: mydata,
                colNames: ['Inv No', 'Date', 'Client', 'Amount', 'Tax', 'Total', 'Status', 'Notes'],
                colModel: [
                    { name: 'id', index: 'id', width: 65, sorttype: 'int', hidden: true },
                    { name: 'invdate', index: 'invdate', width: 120, align: 'center', formatter: 'date',
                      formatoptions: { newformat: 'd-M-Y' } },
                    { name: 'name', index: 'name', editable: true, width: 120 },
                    { name: 'amount', index: 'amount', editable: true, width: 70, formatter: 'number', align: 'right' },
                    { name: 'tax', index: 'tax', editable: true, width: 60, formatter: 'number', align: 'right' },
                    { name: 'total', index: 'total', editable: true, width: 60, formatter: 'number', align: 'right' },
                    { name: 'status', index: 'status', editable:true, width: 90, sorttype: 'int',
                      edittype: 'select', formatter:'select', align: 'center',
                      editoptions: { value:"1:Confirmed;2:Open;3:Rejected" }
                    },
                    { name: 'note', index: 'note', width: 100, sortable: false }
                ],
                rowNum: 1000,
                pager: '#pager',
                rownumbers: true,
                viewrecords: true,
                sortname: 'invdate',
                sortorder: "desc",
                caption: "afterSaveCell Issue",
                height: "100%",
                cellEdit: true,
                cellsubmit: 'clientArray',
                footerrow:true,
                loadComplete: function () {
                    calculateTotal();
                },
                afterSaveCell: function (rowid, name, val, iRow, iCol) {
                    calculateTotal();
                }
            });
            setInterval(calculateTotal, 1000);
            grid.jqGrid('editCell',10,3,true);
        });
    //]]>
    </script>
</head>

<body>
    <table id="list"><tr><td/></tr></table>
    <div id="pager"></div>
</body>
</html>