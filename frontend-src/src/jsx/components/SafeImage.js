import React, { useEffect, useState } from "react";

/** Image with a graceful placeholder: the wrapper gets `.broken` when the file fails to load. */
function SafeImage({ src, alt = "", className = "", icon = "fa-newspaper", style }) {
    const [broken, setBroken] = useState(!src);
    useEffect(() => { setBroken(!src); }, [src]);
    return (
        <div className={`hm-media ${broken ? "broken" : ""} ${className}`} style={style}>
            {!broken ? <img src={src} alt={alt} onError={() => setBroken(true)} /> : null}
            <span className="ph" aria-hidden="true"><i className={`fas ${icon}`} /></span>
        </div>
    );
}

export default SafeImage;
