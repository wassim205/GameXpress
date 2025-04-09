import { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";

const Logout = () => {

    const navigate = useNavigate();
    
    useEffect(() => {
        const logout = async () => {
            try {
                await axios.post("http://127.0.0.1:8000/api/v1/admin/logout", {}, {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem("token")}`,
                    },
                });
            } catch (error) {
                console.error("Logout error:", error);
            } finally {
                localStorage.removeItem("token");
                navigate("/login");
            }
        };
        logout();
    }, [navigate]);
    return <p className="text-center mt-10">Logging out...</p>
};

export default Logout