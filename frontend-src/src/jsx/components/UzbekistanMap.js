import React, { useEffect, useState } from "react";
import HighchartsReact from "highcharts-react-official";
import Highcharts from "highcharts/highmaps";
import mapDataUzbekistan from "@highcharts/map-collection/countries/uz/uz-all.geo.json";
import RegionService from "../../services/cruds/RegionService";
import { TR } from "../../utils/helpers";
const UzbekistanMap = (props) => {
    const {
        handleSelectRegion,
        selectedRegion,
        setSelectedRegion,
        lang
    } = props;
    const [regions, setRegions] = useState([]); // Keep track of region data
    const [chartTitle, setChartTitle] = useState("");
    const chartRef = React.useRef(null);

    const regionData = [
        { "hc-key": "uz-fa", name: TR(lang, "regions.uz_fa"), soato_id: 1730, color: '#6610f2' }, // Farg'ona viloyati uchun rang
        { "hc-key": "uz-tk", name: TR(lang, "regions.uz_tk"), soato_id: 1726, color: '#ffd700' }, // Toshkent shahri uchun rang
        { "hc-key": "uz-an", name: TR(lang, "regions.uz_an"), soato_id: 1703, color: '#4d06a5' }, // Andijon viloyati uchun rang
        { "hc-key": "uz-ng", name: TR(lang, "regions.uz_ng"), soato_id: 1714, color: '#dc3545' }, // Namangan viloyati uchun rang
        { "hc-key": "uz-ji", name: TR(lang, "regions.uz_ji"), soato_id: 1708, color: '#fd7e14' }, // Jizzax viloyati uchun rang
        { "hc-key": "uz-si", name: TR(lang, "regions.uz_si"), soato_id: 1724, color: '#ffc107' }, // Sirdaryo viloyati uchun rang
        { "hc-key": "uz-ta", name: TR(lang, "regions.uz_ta"), soato_id: 1727, color: '#13b497' }, // Toshkent viloyati uchun rang
        { "hc-key": "uz-bu", name: TR(lang, "regions.uz_bu"), soato_id: 1706, color: '#20c997' }, // Buxoro viloyati uchun rang
        { "hc-key": "uz-kh", name: TR(lang, "regions.uz_kh"), soato_id: 1733, color: '#17a2b8' }, // Xorazm viloyati uchun rang
        { "hc-key": "uz-qr", name: TR(lang, "regions.uz_qr"), soato_id: 1735, color: '#94618E' }, // Qoraqalpog‘iston Respublikasi uchun rang
        { "hc-key": "uz-nw", name: TR(lang, "regions.uz_nw"), soato_id: 1712, color: '#00bcd4' }, // Navoiy viloyati uchun rang
        { "hc-key": "uz-sa", name: TR(lang, "regions.uz_sa"), soato_id: 1718, color: '#ff6f61' }, // Samarqand viloyati uchun rang
        { "hc-key": "uz-qa", name: TR(lang, "regions.uz_qa"), soato_id: 1710, color: '#6eadf1' }, // Qashqadaryo viloyati uchun rang
        { "hc-key": "uz-su", name: TR(lang, "regions.uz_su"), soato_id: 1722, color: '#4cb32b' }, // Surxondaryo viloyati uchun rang
    ];

    // Update the region data
    useEffect(() => {
        RegionService.select(true, false, "", { country_id: 19 }).then((res) => {
            const temp = [...regionData];
            res.data.data.forEach(element => {
                const index = temp.findIndex(e => element.soato_id === e.soato_id);
                if (index !== -1) {
                    temp[index].id = element.id;
                }
            });
            setRegions(temp);
        })
    }, []);

    const handleClickRegion = (event) => {
        const regionKey = event.point.options['soato_id']; // Get the key of the clicked region
        const newColor = "#FF0000"; // New color for the clicked region
        const borderColor = "#BADA55";
        const selected = regions.find(element => element.id === selectedRegion);
        const oldSelectedRegion = selectedRegion ? selected.soato_id : null; // Get the currently selected region (if any)

        // If clicked region is already selected, unselect it by reverting the color
        if (regionKey === oldSelectedRegion) {
            // Revert the clicked region's color to its original color
            const updatedRegions = regions.map((region) =>
                region['soato_id'] === regionKey
                    ? {
                        ...region,
                        color: regionData.find(r => r['soato_id'] === regionKey)?.color,
                        borderColor: "#000000",
                        borderWidth: 0.8
                    } // Revert to original color
                    : region
            );
            setChartTitle("")
            setRegions(updatedRegions); // Update state with reverted color
            setSelectedRegion(null); // Unselect the region
            handleSelectRegion(null)

        } else {
            // If a new region is selected, revert the old selected region back to its original color
            const updatedRegions = regions.map((region) => {
                if (region['soato_id'] === regionKey) {
                    return {
                        ...region,
                        color: newColor,
                        borderColor,
                        borderWidth: 2
                    }; // Set color for the selected region
                } else if (region['soato_id'] === oldSelectedRegion) {
                    return {
                        ...region,
                        color: regionData.find(r => r['soato_id'] === region['soato_id'])?.color,
                        borderColor: "#000000",
                        borderWidth: 0.8
                    }; // Revert old region to original color
                }
                return region;
            });

            setRegions(updatedRegions); // Update state with new region selection
            const selected = regions.find(element => element.soato_id === regionKey);
            setChartTitle(selected.name)
            setSelectedRegion(selected.id); // Set new selected region
            handleSelectRegion(selected.id)
        }
    };

    const chartOptions = {
        chart: {
            map: mapDataUzbekistan,
            spacing: [0, 0, 0, 0],
            margin: [0, 0, 0, 0],
            events: {
                load: function () {
                    chartRef.current = this; // Highcharts ichida `this` chartga teng
                }
            },
        },
        title: {
            text: chartTitle,
        },
        mapNavigation: {
            enabled: false,
        },
        credits: {
            enabled: false, // Bu joyda pastdagi "Highcharts.com" va "Natural Earth" kreditlarini o'chiramiz
        },
        legend: {
            enabled: false, // Bu joyda legendni butunlay o'chirib qo'yamiz
        },
        colorAxis: {
            min: 0,
        },
        series: [
            {
                states: {
                    hover: {
                        borderColor: "#BADA55"
                    }
                },
                dataLabels: {
                    enabled: false
                },
                allAreas: false,
                data: regions,
                borderColor: "#000000",
                borderWidth: 0.7,
                point: {
                    events: {
                        click: handleClickRegion
                    }
                }
            }
        ]
    };

    return (
        <div>
            <HighchartsReact
                highcharts={Highcharts}
                options={chartOptions}
                constructorType={"mapChart"}
                updateArgs={[true, true, true]}
                ref={chartRef}
            />
        </div>
    );
};

export default UzbekistanMap;
