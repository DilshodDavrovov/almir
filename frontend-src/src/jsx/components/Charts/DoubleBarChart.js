import React from "react";
import { Bar } from "react-chartjs-2";

export default function DoubleBarChart(props) {


    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: "right",
                labels: {
                    usePointStyle: true,
                    padding: 15
                }
            },
        },
        scales: {
            y: {
                beginAtZero: true,
            },
        },
    };
    const { data } = props;
    return <Bar data={data} height={150} options={options} />
}
