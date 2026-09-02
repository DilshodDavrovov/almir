import React from "react";
import {saveAs} from "file-saver";
import * as ExcelJs from "exceljs";
import { TR } from "../../../utils/helpers";
import { ExcelCells, NumberToStr, formatNumber } from "../../../utils";
import { connect } from "react-redux";
const ExportExcel = ({ rows, headerColumns, fileName,loading, lang, disabled, total, totalExcel}) => {
    const fTableData=(columns)=>{
        const arr = [];
        const tempForAll = {};
        totalExcel = totalExcel || total;
      if(totalExcel){
        headerColumns.forEach((column,index)=>{
            if(!column.hide){
                let role = column.role;
                if(role){
                  if(role === 'name'){
                    tempForAll[column.accessor] = TR(lang,"table.mainTurnOver")
                  } else if(role === 'percent'){
                      tempForAll[column.accessor] = "100%";
                  } else if(role === 'price' || role === 'price_uz' || role === 'count'){
                    tempForAll[column.accessor] =  NumberToStr(_.get(totalExcel, column.excel_total_accessor)) || 0
                  } else if(role === 'diffPrice.price' || role === 'diffPrice.price_uz' || role === 'diffPrice.qty'){
                    tempForAll[column.accessor] = NumberToStr(_.get(totalExcel, column.excel_total_accessor) || 0)
                  } else {
                    tempForAll[`item${index}`] = ""
                  }
                } else {
                  tempForAll[`item${index}`] = ""
                }
            }
        })
        arr.push(tempForAll)
      }
      rows.forEach(element => {
        const temp = {};
        columns.forEach((column,index) => {
            if(!column.hide){
                const accessor = column.accessor;
                const value = element.cells[index].value
                const role = column.role;
                if(role === 'percent' || role === 'dif_percent'){
                    temp[column.accessor] = (value || 0 )/ 100;
                } else if(Array.isArray(value)){
                    if(column.location === 'drugs_crud'){
                        temp[column.accessor] = (value.map(v => `(${v.counter}) ${v.full_name}`)).join("\n");
                    } else {
                        temp[column.accessor] = (value.map(v => v.percent ? `${v.name} = ${v.percent} %`: '')).join("\n");
                    }
                } else if(typeof value ==='object'){
                    if(column.location ==='drc_user'){
                        temp[column.accessor] = `${value.first_name + " " + value.last_name}`
                    }
                } else {
                    if(value === undefined || value === ""){
                        temp[accessor] = TR(lang, "content.noData")
                    } else {
                        if(!isNaN(value)){
                            if(column.location === 'rx_otc'){
                                temp[accessor] = (value) ? 'OTC' : 'RX';
                            } else {
                                temp[accessor] = formatNumber(value);              
                            }
                        } else {
                            temp[accessor] = value;              
                        }
                    }
                }
            }
        })
        arr.push(temp);
      });
      return arr;
    }
  
    const fWscols=(columns)=>{
      const temp=[];
      columns.forEach((column, id)=>{
        if(!column.hide){
            temp.push(
              Math.max(
                ...rows.map(item => {
                    const value = item.cells[id].value   
                    if(Array.isArray(value)){
                        try {
                            if(column.location === 'drugs_crud'){
                                return Math.max(...value.map(val => val.full_name ? val.full_name.length : 10 + 17))
                            } else {
                                return Math.max(...value.map(val => val.name.length + 17))
                            }
                        } catch (error) {}
                    } else {
                        if(value){
                            return value.toString().length + 5;
                        } else {
                            return 6;
                        }
                    }
                }),
                ((column.HeaderTitle)?
                    (column.HeaderTitle.length + 10)/2
                    :(column.Header.length + 10)/2)
              )
            )
        }
      })
      return temp;
    }
    const fHeading=(columns)=>{
      const temp=[];
      columns.forEach((column)=>{
        if(!column.hide){
            const obj={};
            obj.header = column.HeaderTitle || column.Header; 
            obj.key = column.accessor;
            temp.push(obj);
        }
      })
      return temp;
    }

    const  exportToExcel=(fileName)=>{
      const columns = headerColumns;
      let workbook = new ExcelJs.Workbook()
      let worksheet = workbook.addWorksheet('Sheet')
      const Headers = fHeading(columns);
      const data = fTableData(columns);
      const wscols = fWscols(columns);
      worksheet.columns = [...Headers]
      worksheet.columns.forEach((column,index) => { column.width = wscols[index]; })
      worksheet.addRows(data)
      worksheet.getRow(1).font = {bold: true, 'color': {argb:'000000'}, 'name': 'Calibri'}
      data.forEach((key, id)=>{
        Object.keys(key).forEach((objKey, index)=> {
          const text = `${ExcelCells(index)}${1}`;
          worksheet.getCell(text).border = {
            top: {style:'thin', color:{argb:'00000000'}},
            left: {style:'thin', color:{argb:'00000000'}},
            bottom: {style:'thin', color:{argb:'00000000'}},
            right: {style:'thin', color:{argb:'00000000'}}  
          };
          worksheet.getCell(text).alignment = { wrapText: true };
        })


        Object.keys(key).forEach((objKey, index)=> {
            const text = `${ExcelCells(index)}${id+2}`;
            if(id === 0 && total){
              worksheet.getCell(text).fill={
                type: "pattern",
                pattern: "solid",
                fgColor: {
                  argb: "FFFFCC00"
                }
              };
            }
            worksheet.getCell(text).border = {
              left: {style:'hair', color:{argb:'00000000'}},
              bottom: {style:'hair', color:{argb:'00000000'}},
              right: {style:'hair', color:{argb:'00000000'}}  
            };
            let role = rows[0] ? rows[0].cells[index].column.role: '';
            if(role){
              if(role === 'percent' || role === 'dif_percent'){
                worksheet.getCell(text).numFmt = '0.000\%'
              } else if(role === 'price' || role === 'priceSingle' || role === 'sum' || 
                 role === 'price_uz' || role === 'price_usd' || role === 'sum_price_uzs' || 
                 role === 'sum_price_usd' || role === 'diffPrice.price' || role === 'diffPrice.price_uz'){
                worksheet.getCell(text).numFmt = '#,##0.00'
              } 
            }
            worksheet.getCell(text).alignment = { wrapText: true };
        })
      })

      workbook.xlsx.writeBuffer().then(data => {
        saveAs(new Blob([data]), `${fileName}.xlsx`);
       });
    }
  return (
     
    <button
        onClick={e => exportToExcel(fileName)}
        title={TR(lang, "content.export")}
        disabled={disabled}
        type="button"
        className={`btn btn-outline-primary media-btn992 media-btn rounded-2 hover-none ${(loading) ? 'disabled' : ""}`}
        style={{
            margin: "0px 2px",
            whiteSpace: 'nowrap'
        }}
    >
        <i className="fas fa-file-excel mr-1 me-2" aria-hidden="true"></i>
        {/*<span>{TR(lang, "content.export")}</span>*/}
    </button>
  );
};
const mapStateToProps = (state) => {
    return {
      lang: state.language.lang
    };
  };
  
  export default connect(mapStateToProps)(ExportExcel);