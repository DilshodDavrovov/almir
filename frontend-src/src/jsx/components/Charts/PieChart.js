import React from "react";
import { Pie } from "react-chartjs-2";

export default function PieChart(props) {
    const { data } = props
    
    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function (context) {
                        return context.label;
                    },
                    title: function () {
                        return '';
                    },
                },
            },
            legend: {
                position: "right",
                labels: {
                    usePointStyle: true,
                    padding: 15
                }
            },
        },
    };
    return <Pie data={data} options={options} />
};
