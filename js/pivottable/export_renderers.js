function getcurrenttimestamp() {
	var now = new Date();
       	var date = [ now.getFullYear(), now.getMonth()+1, now.getDate() ];
      	var time = [ now.getHours(), now.getMinutes() ];
   	return date.join('')+"_"+time.join('');
}

(function() {
  var callWithJQuery;

  callWithJQuery = function(pivotModule) {
    if (typeof exports === "object" && typeof module === "object") {
      return pivotModule(require("jquery"));
    } else if (typeof define === "function" && define.amd) {
      return define(["jquery"], pivotModule);
    } else {
      return pivotModule(jQuery);
    }
  };

  callWithJQuery(function($) {
    return $.pivotUtilities.export_renderers = {
      "TSV Export": function(pivotData, opts) {
        var agg, colAttrs, colKey, colKeys, defaults, i, j, k, l, len, len1, len2, len3, len4, len5, m, n, r, result, row, rowAttr, rowAttrs, rowKey, rowKeys, text;
        defaults = {
          localeStrings: {}
        };
        opts = $.extend(defaults, opts);
        rowKeys = pivotData.getRowKeys();
        if (rowKeys.length === 0) {
          rowKeys.push([]);
        }
        colKeys = pivotData.getColKeys();
        if (colKeys.length === 0) {
          colKeys.push([]);
        }
        rowAttrs = pivotData.rowAttrs;
        colAttrs = pivotData.colAttrs;
        result = [];
        row = [];
        for (i = 0, len = rowAttrs.length; i < len; i++) {
          rowAttr = rowAttrs[i];
          row.push(rowAttr);
        }
        if (colKeys.length === 1 && colKeys[0].length === 0) {
          row.push(pivotData.aggregatorName);
        } else {
          for (j = 0, len1 = colKeys.length; j < len1; j++) {
            colKey = colKeys[j];
            row.push(colKey.join("-"));
          }
        }
        result.push(row);
        for (k = 0, len2 = rowKeys.length; k < len2; k++) {
          rowKey = rowKeys[k];
          row = [];
          for (l = 0, len3 = rowKey.length; l < len3; l++) {
            r = rowKey[l];
            row.push(r);
          }
          for (m = 0, len4 = colKeys.length; m < len4; m++) {
            colKey = colKeys[m];
            agg = pivotData.getAggregator(rowKey, colKey);
            if (agg.value() != null) {
              row.push(agg.value());
            } else {
              row.push("");
            }
          }
          result.push(row);
        }
        text = "";
        for (n = 0, len5 = result.length; n < len5; n++) {
          r = result[n];
          text += r.join("\t") + "\n";
        }

	//Returns 2 jQuery objects: 1 for the link to download which also asks for file name and 2 for displaying it in the textbox
	return $('<a id="download" href="data:text/tsv,'+encodeURIComponent(text)+'"> Download as a TSV File </a>').click(function() {
			var outputFile = "CiviREBUX_ReportTSV_"+getcurrenttimestamp()+'.tsv';
			$('#download').attr('download',outputFile);
		});
     },

    "CSV Export": function(pivotData, opts) {
        var agg, colAttrs, colKey, colKeys, defaults, i, j, k, l, len, len1, len2, len3, len4, len5, m, n, r, result, row, rowAttr, rowAttrs, rowKey, rowKeys, text;
        defaults = {
          localeStrings: {}
        };
        opts = $.extend(defaults, opts);
        rowKeys = pivotData.getRowKeys();
        if (rowKeys.length === 0) {
          rowKeys.push([]);
        }
        colKeys = pivotData.getColKeys();
        if (colKeys.length === 0) {
          colKeys.push([]);
        }
        rowAttrs = pivotData.rowAttrs;
        colAttrs = pivotData.colAttrs;
        result = [];
        row = [];
        for (i = 0, len = rowAttrs.length; i < len; i++) {
          rowAttr = rowAttrs[i];
          row.push(rowAttr);
        }
        if (colKeys.length === 1 && colKeys[0].length === 0) {
          row.push(pivotData.aggregatorName);
        } else {
          for (j = 0, len1 = colKeys.length; j < len1; j++) {
            colKey = colKeys[j];
            row.push(colKey.join("-"));
          }
        }
        result.push(row);
        for (k = 0, len2 = rowKeys.length; k < len2; k++) {
          rowKey = rowKeys[k];
          row = [];
          for (l = 0, len3 = rowKey.length; l < len3; l++) {
            r = rowKey[l];
            row.push(r);
          }
          for (m = 0, len4 = colKeys.length; m < len4; m++) {
            colKey = colKeys[m];
            agg = pivotData.getAggregator(rowKey, colKey);
            if (agg.value() != null) {
              row.push(agg.value());
            } else {
              row.push("");
            }
          }
          result.push(row);
        }
        text = "";
        for (n = 0, len5 = result.length; n < len5; n++) {
          r = result[n];
          text += r.join(",") + "\n";
        }

      	return $('<a id="download" href="data:text/csv,'+encodeURIComponent(text)+'"> Download as a CSV File </a>').click(function() {
			var outputFile = "CiviREBUX_ReportCSV_"+getcurrenttimestamp()+'.csv';
                        $('#download').attr('download',outputFile);
                });	
      }
    };
  });
}).call(this);

//# sourceMappingURL=export_renderers.js.map
