import ciDictionary from "../langs/uz.json";
import ruDictionary from "../langs/ru.json";
import enDictionary from "../langs/en.json";
export function TR(lang,langRoute){
    let text;
    switch(lang){
        case "рус":
            text = _.get(ruDictionary, langRoute);
            break;
        case "eng":
            text =  _.get(enDictionary, langRoute);
            break;
        case "ўзб":
            text =  _.get(ciDictionary, langRoute);
            break;
        default:
    }
    return text;
}

export function genPass(len) {

    let password = "";
    let symbols = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for (let i = 0; i < len; i++)
    {
        password += symbols.charAt(Math.floor(Math.random() * symbols.length));     
    }

    return password;
}

// export function generateData(data, columns, TR, lang){
//     return data.map(key => {
//         const temp = [];
//         columns.forEach(column => {
//             if(column.accessor === 'status'){
//                 temp.push(
//                     <div className="d-flex align-items-center">
//                         <i className="fa fa-circle text-success me-1"/>
//                         {key.is_active ? TR(lang, 'content.activeOne') : TR(lang, 'content.unactiveOne')}
//                     </div>
//                 )
//             } else {
//                 temp.push(key[column.accessor]);
//             }
//         })
//         return temp;
//     })
// }

export function canPreviousPage(page){
    return page > 1;
}

export function canNextPage(page, totalPages){
    return page < totalPages;
}

export function checkForMacOS() {
    const platform = window.navigator.platform;
    return platform.includes("Mac") || platform.includes("MacIntel");
}