import { toast } from 'react-toastify';
import { createAutoCorrectedDatePipe } from 'text-mask-addons'

export const autoCorrectedDatePipe = createAutoCorrectedDatePipe('dd/mm/yyyy');
export function diffSelector(count) {
    if (count === 1) return 0;
    if (count === 2) return 1;
    if (count === 3) return 1;

}
export function isDiffPeriod(dateLenght, period) {
    return (dateLenght % 2 == 0 && period % 2 == 0) || (dateLenght % 2 == 1 && period - 1 > 0)
}
export function isInDate(date, period) {
    return date > period.fromDate && date < period.toDate
}
export function GetDiffferens(value = 0, toFixed, mark) {
    if (value) {
        return (
            <div className="text-nowrap">
                <i
                    style={value > 0 ? { color: "green" } : { color: "red" }}
                    className={` ${(value > 0) ? `fa-arrow-up` : `fa-arrow-down`} fas me-1`} />
                {NumberToStr(Number(value).toFixed(toFixed))} {mark || ''}
            </div>
        )
    } else {
        return (
            <div className="text-nowrap">
                <i style={{ color: "blue" }} className={`fas fa-equals me-1`} />
                {NumberToStr(Number(0).toFixed(toFixed))} {mark || ''}
            </div>
        )
    }
}
function formatter(str) {
    str = str.split(' ').join('');
    let bool = true;
    return str.split('').map((key, index) => {
        if (isNaN(key)) {
            if ((key === '.')) {
                if (bool) {
                    bool = false;
                    if (index === 0) return '0.'; else return '.';
                } else return '';
            } else return '';
        } else {
            return key;
        }
    }).join('');
}
export function NumberToStr(number) {
    if (number && !isNaN(number) && isFinite(number)) {
        let string = number.toString();
        const arr = string.split('.');
        let str = "";
        let count = 0;
        let temp = "";
        if (arr[0].length <= 3) return number;
        for (let j = arr[0].length - 1; j >= 0; j--) {
            count++;
            temp = arr[0][j] + temp;
            if (count === 3) {
                if (str.length > 0) {
                    str = temp + ' ' + str;
                } else {
                    str = temp;
                }
                temp = '';
                count = 0;
            }
        }
        if (temp.length > 0) str = temp + ' ' + str;
        if (arr.length > 1) str = str + '.' + arr[1].slice(0, 2);
        return str;
    } else {
        return 0;
    }
}
export function StrtoNumber(Str) {
    return formatter(Str.toString());
}
export function getId(rating, id) {
    for (let i = 0; i < rating.length; i++) {
        if (id === rating[i].id) return i + 1;
    }
    return NaN;
}

export function CalculatePercent(a, b) {
    return +((a / b) * 100).toFixed(2);
}
export function CalculateTops(data, price, count) {
    let temp = [...data];
    temp.sort(function (a, b) { return b.usd - a.usd; });
    temp.forEach((e, i) => e.perc = Math.round((e.usd * 10000) / price) / 100)
    return temp.splice(0, count);
}

export function GetTops(Header, accessor, noData, show) {
    return {
        show: show ? true : false,
        Header: Header,
        accessor: accessor,
        Cell: (props) => {
            if (props.value?.length) {
                return props.value.map(key => {
                    return <div className='m-0'>{key['name_uz']}={key.perc} % </div>
                })
            } else {
                return noData
            }
        }
    }

}
export function GetDiffferensExcel(value, st) {
    if (value) {
        return NumberToStr(Number(value).toFixed(2))
    } else {
        return ' = 0.00'
    }
}
export function MakeObj(Header, accessor, fixed, ret, show) {
    return {
        show: (show) ? true : false,
        Header: Header,
        accessor: accessor,
        Cell: (props) => {
            if (props.value) {
                return NumberToStr(Number(props.value).toFixed(fixed)) + ret;
            } else {
                return (0).toFixed(fixed) + ret;
            }
        }
    }
}
export function ParseDateToString(str) {
    const date = new Date(str);
    const year = date.getFullYear();
    const month = getDate(date.getMonth() + 1);
    const day = getDate(date.getDate());
    return `${day}.${month}.${year}`
}
export function formatDate(str) {
    const date = new Date(str)
    const year = date.getFullYear();
    const month = getDate(date.getMonth() + 1);
    const day = getDate(date.getDate());
    const minutes = getDate(date.getMinutes());
    const seconds = getDate(date.getSeconds());
    const hour = getDate(date.getHours());
    return `${day}.${month}.${year}  ${hour}:${minutes}:${seconds}`
}
export function formatDateToDay(str) {
    const date = new Date(str)
    const year = date.getFullYear();
    const month = getDate(date.getMonth() + 1);
    const day = getDate(date.getDate());
    return `${day}-${month}-${year}`
}
function getDate(str) {
    if (+str < 10) return `0${str}`
    return str;
}
export function DateFormat(dateProp) {
    if (dateProp) {
        const parsedDate = new Date(dateProp.slice(0, 10));
        let date = parsedDate.getDate().toString();
        let month = parsedDate.getMonth() + 1;
        month = month.toString();
        if (date.length === 1) date = '0' + date;
        if (month.length === 1) month = '0' + month;
        const dateStr = date + '/' + month + '/' + parsedDate.getFullYear();
        return dateStr;
    }
    return ""
}
export function stringToDate(_date, _format, _delimiter) {
    const formatLowerCase = _format.toLowerCase();
    const formatItems = formatLowerCase.split(_delimiter);
    const dateItems = _date.split(_delimiter);
    const monthIndex = formatItems.indexOf("mm");
    const dayIndex = formatItems.indexOf("dd");
    const yearIndex = formatItems.indexOf("yyyy");
    const month = parseInt(dateItems[monthIndex]) - 1;
    const formatedDate = new Date(dateItems[yearIndex], month, dateItems[dayIndex]);
    return formatedDate;
}
export function ExcelCells(number) {
    if (number >= 26) {
        return (String.fromCharCode(65 + Math.floor(number / 26) - 1) + String.fromCharCode(65 + number % 26))
    } else {
        return String.fromCharCode(65 + number);
    }
}
export function customFilter(option, searchText) {
    if (searchText.length < 3) return false;
    if (
        option.label.toLowerCase().indexOf(searchText.toLowerCase()) > -1
    ) {
        return true;
    } else {
        return false;
    }
}
export function differencePercent(x1, x2) {
    if (x1 === 0 && x2 === 0) {
        return 0
    } else if (x1 === 0) {
        return 100
    } else if (x2 === 0) {
        return -100
    } else {
        return (100 * (x2 - x1) / x1)
    }
}
export function getThisYear(lastDate) {
    const date = stringToDate(lastDate, 'dd-mm-yyyy', '-');
    const year = date.getFullYear();
    const month = getDate(date.getMonth() + 1);
    const day = getDate(date.getDate());
    return { fromDate: `01-01-${year}`, toDate: `${day}-${month}-${year}` }
}
export function getKvartal() {
    const date = new Date();
    const year = date.getFullYear();
    const month = getDate(date.getMonth() + 1);
    const day = getDate(date.getDate());
    if (month < 4) {
        return { fromDate: `01-01-${year}`, toDate: `${day}-${month}-${year}` }
    } else if (month < 7) {
        return { fromDate: `01-04-${year}`, toDate: `${day}-${month}-${year}` }
    } else if (month < 10) {
        return { fromDate: `01-07-${year}`, toDate: `${day}-${month}-${year}` }
    } else if (month > 9) {
        return { fromDate: `01-10-${year}`, toDate: `${day}-${month}-${year}` }
    }
}
// export function getLastKvartal(){
//     const date = new Date();
//     const year = date.getFullYear();
//     let month = getDate(date.getMonth()+1);
//     if(month < 4){
//         return {fromDate:`01-10-${year-1}`, toDate:`31-12-${year-1}`}
//     } else if(month < 7){
//         return {fromDate:`01-01-${year}`, toDate:`31-03-${year}`}
//     } else if(month < 10){
//         return {fromDate:`01-07-${year}`, toDate:`30-06-${year}`}
//     }else if(month > 9){
//         return {fromDate:`01-10-${year}`, toDate:`30-09-${year}`}
//     }
// }
export function getStrKvartal(year, kvartal) {
    if (kvartal === 1) {
        return { fromDate: `${year}-01-01`, toDate: `${year}-03-31` }
    } else if (kvartal === 2) {
        return { fromDate: `${year}-04-01`, toDate: `${year}-06-30` }
    } else if (kvartal === 3) {
        return { fromDate: `${year}-07-01`, toDate: `${year}-09-30` }
    } else if (kvartal === 4) {
        return { fromDate: `${year}-10-01`, toDate: `${year}-12-31` }
    }
}
function getYear(date) {
    return new Date(date).getFullYear()
}
export function getSelectKvartal(obj) {
    const year = getYear(obj.fromDate);
    const fromDate = obj.fromDate.slice(5)
    let kvartal;
    if (fromDate === '01-01') {
        kvartal = 1;
    } else if (fromDate === '04-01') {
        kvartal = 2;
    } else if (fromDate === '07-01') {
        kvartal = 3;
    } else if (fromDate === '10-01') {
        kvartal = 4;
    }
    return ({ year, kvartal })
}
function checkDefColList(str, defColList) {
    return !!defColList.find(key => key === str)
}
export function getHiddenColumns(columns, defColList) {
    let temp = [];
    columns.forEach(column => {
        if (!checkDefColList(column.accessor, defColList)) {
            temp.push(column.accessor)
        }
    })
    return temp
}

export function showToast(status, message) {
    if (status === 'error') {
        toast.error(message, {
            position: "top-right",
            autoClose: 5000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            progress: undefined,
        });
    } else if (status === 'warning') {
        toast.warn(message, {
            position: "top-right",
            autoClose: 5000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            progress: undefined,
        });
    } else if (status === 'success') {
        toast.info(message, {
            position: "top-right",
            autoClose: 5000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            progress: undefined,
        });
    }
}
export function buildParams(obj) {
    const params = new URLSearchParams()

    Object.entries(obj).forEach(([key, value]) => {
        if (typeof value === 'object') {
            params.append(key, JSON.stringify(value));
        } else {
            params.append(key, value ? value.toString() : 'null')
        }
    });

    return params.toString()
}
export function parseParams(str) {
    const params = Object.fromEntries(new URLSearchParams(str));
    try {
        Object.entries(params).forEach(([key, value]) => {
            params[key] = JSON.parse(value);
        });
    } catch (err) { }
    return params;
}
export function checkRole(acceptedRole, role) {
    return role && Number(role) && Number(acceptedRole) && Number(acceptedRole) >= Number(role);
}

export function formatNumber(number) {
    let numberStr = number.toString();
    const temp = numberStr.split('.');
    if (temp[1] && temp[1].length > 2) {
        let str = temp[1];
        while (str.length > 2) {
            if (str[str.length - 1] === '0') {
                str = str.slice(0, -1);
            } else {
                break;
            }
        }
        numberStr = temp[0] + '.' + str;
    }
    return parseFloat(numberStr);
}