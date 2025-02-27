import { useState, useEffect } from "react";

// @mui material components
import Card from "@mui/material/Card";
import Grid from "@mui/material/Grid";
import AppBar from "@mui/material/AppBar";
import Tabs from "@mui/material/Tabs";
import Tab from "@mui/material/Tab";

// React components
import SoftBox from "components/SoftBox";
import SoftTypography from "components/SoftTypography";
import SoftAvatar from "components/SoftAvatar";

// React icons
import Settings from "essentials/Icons/Settings";
import Document from "essentials/Icons/Document";
import Cube from "essentials/Icons/Cube";

// React base styles
import breakpoints from "assets/theme/base/breakpoints";

// Images
import logo from "assets/images/logo.png";
import bgImage from "assets/images/banner.jpg";
import Information from "../Information";
import Security from "../Security";
import Edit from "../Edit";
import { useStateContext } from "context/ContextProvider";
import { useDashboardData } from "layouts/dashboard/data/dashboardRedux";
import { apiRoutes } from "components/Api/ApiRoutes";
import axios from "axios";
import { passToSuccessLogs } from "components/Api/Gateway";
import { passToErrorLogs } from "components/Api/Gateway";
import { toast } from "react-toastify";
import FixedLoading from "components/General/FixedLoading";

function ProfileContainer() {
  const [tabsOrientation, setTabsOrientation] = useState("horizontal");
  const [tabValue, setTabValue] = useState(0);

  const {authUser, polls} = useDashboardData({
    authUser: true, 
    polls: false, 
  });

  const [menu, setMenu] = useState("profile");

  const setProfile = () => {
    setMenu("profile");
    setTabValue(0);
  };

  const setEdit = () => {
    setMenu("edit");
    setTabValue(1);
  };

  const setSettings = () => {
    setMenu("security");
    setTabValue(2);
  };

  useEffect(() => {
    // A function that sets the orientation state of the tabs.
    function handleTabsOrientation() {
      return window.innerWidth < breakpoints.values.xsm
        ? setTabsOrientation("vertical")
        : setTabsOrientation("horizontal");
    }

    /** 
     The event listener that's calling the handleTabsOrientation function when resizing the window.
    */
    window.addEventListener("resize", handleTabsOrientation);

    // Call the handleTabsOrientation function to set the state with the initial value.
    handleTabsOrientation();

    // Remove event listener on cleanup
    return () => window.removeEventListener("resize", handleTabsOrientation);
  }, [tabsOrientation]);

  const handleSetTabValue = (event, newValue) => setTabValue(newValue);

  const {token, role, user} = useStateContext();
  const YOUR_ACCESS_TOKEN = token; 
  const headers = {
    'Authorization': `Bearer ${YOUR_ACCESS_TOKEN}`
  };
  
  const [User, setUser] = useState([]);
  const currentFileName = "layouts/profile/components/ProfileContainer/index.js";
  const [reload, setReload] = useState(true);
  const username = user;

  useEffect(() => {
    if (reload) {
      setReload(true);
      axios.get(apiRoutes.profileRetrieved, { params: { username }, headers })
        .then(response => {
          if (response.data.status === 200) {
            setUser(response.data.user);
          } else {
            toast.error(`${response.data.message}`, { autoClose: true });
          }
          passToSuccessLogs(response.data, currentFileName);
          setReload(false);
        })  
        .catch(error => {
          passToErrorLogs(`Users not Fetched!  ${error}`, currentFileName);
          setReload(false);
        });
    }
  }, [reload]);

  const UpdateLoading = (reloading) => {
    setReload(reloading);
  };

  return (
    <>
    <SoftBox position="relative" mt={3}>
      <SoftBox
        display="flex"
        alignItems="center"
        position="relative"
        minHeight="18.75rem"
        borderRadius="xl"
        sx={{
          backgroundImage: ({ functions: { rgba, linearGradient }, palette: { gradients } }) =>
            `${linearGradient(
              rgba(gradients.info.main, 0.3),
              rgba(gradients.info.state, 0.2)
            )}, url(${User.client_banner ? `data:image/jpg;base64,${encodeURIComponent(User.client_banner)}` : bgImage})`,
          backgroundSize: "cover",
          backgroundPosition: "50%",
          overflow: "hidden",
        }}
      />
      <Card
        sx={{
          backdropFilter: `saturate(200%) blur(30px)`,
          backgroundColor: ({ functions: { rgba }, palette: { white } }) => rgba(white.main, 0.8),
          boxShadow: ({ boxShadows: { navbarBoxShadow } }) => navbarBoxShadow,
          position: "relative",
          mt: -8,
          mx: 3,
          py: 2,
          px: 2,
        }}
      >
        <Grid container spacing={3} alignItems="center">
          <Grid item>
            <SoftAvatar
              src={User.id_picture ? `data:image/*;base64,${User.id_picture}` : logo}
              alt="profile-image"
              variant="rounded"
              size="xl"
              shadow="sm"
            />
          </Grid>
          <Grid item>
            <SoftBox height="100%" mt={0.5} lineHeight={1}>
              <SoftTypography variant="h5" fontWeight="medium">
                {User.first_name}{" "}
                {User.middle_name}{" "}
                {User.last_name}{" "}
              </SoftTypography>
              <SoftTypography variant="button" color="text" fontWeight="medium">
                {User.username}{" "}
              </SoftTypography>
            </SoftBox>
          </Grid>
          <Grid item xs={12} md={5} sx={{ ml: "auto" }}>
            <AppBar position="static">
              <Tabs
                orientation={tabsOrientation}
                value={tabValue}
                onChange={handleSetTabValue}
                sx={{ background: "transparent" }}
              >
                <Tab label="Information" onClick={setProfile} icon={<Cube />} />
                <Tab label="Edit" onClick={setEdit} icon={<Document />} />
                <Tab label="Security" onClick={setSettings} icon={<Settings />} />
              </Tabs> 
            </AppBar>
          </Grid>
        </Grid>
      </Card>
    </SoftBox>
    {menu === "profile" && <Information USER={User}  reload={reload} />}
    {menu === "edit" && <Edit UpdateLoading={UpdateLoading} USER={User}  />}
    {menu === "security" && <Security USER={User}  />}
    </>
  );
}

export default ProfileContainer;
