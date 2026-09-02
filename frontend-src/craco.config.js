// CRA 5 (webpack 5) treats ESM packages as "fully specified" and fails on imports like
// `react/jsx-runtime` coming from react-chartjs-2 with React 17. Relax that rule.
module.exports = {
  webpack: {
    configure: (config) => {
      config.module.rules.push({ test: /\.m?js$/, resolve: { fullySpecified: false } });
      return config;
    },
  },
};
