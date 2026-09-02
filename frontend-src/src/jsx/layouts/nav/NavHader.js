import React, { useContext, useState } from "react";
/// React router dom
import { Link } from "react-router-dom";
import { ThemeContext } from "../../../context/ThemeContext";
import mainLogo from "../../../images/Almir-logo.png"

const NavHader = () => {
  const [toggle, setToggle] = useState(false);
  const { openMenuToggle } = useContext( ThemeContext );
  return (
    <div className="nav-header">
      <Link to="/" className="brand-logo">
        <img src={mainLogo}style={{ width:"100%", height:"auto", objectFit: 'cover'}} />
      </Link>

      <div
        className="nav-control"
        onClick={() => {
          setToggle(!toggle);
          openMenuToggle();
        }}
      >
        <div className={`hamburger ${toggle ? "is-active" : ""}`}>
          <span className="line"></span>
          <span className="line"></span>
          <span className="line"></span>
        </div>
      </div>
    </div>
  );
};

export default NavHader;
