/* =========================================================
   ROCKVIEW PREMIER LEAGUE
   MAIN JAVASCRIPT
   CLEAN VERSION
   ========================================================= */

"use strict";


/* =========================================================
   GLOBAL DATA
   ========================================================= */

let teams = [];
let players = [];
let matches = [];
let standings = [];
let goalkeepers = [];


/* =========================================================
   START APPLICATION
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    console.log("RPL JavaScript loaded successfully.");

    loadTeams();
    loadPlayers();
    loadMatches();
    loadStandings();
    loadGoalkeepers();
    loadTopScorers();
    loadPlayerStatistics();

    setupPlayerSearch();
    setupNavigation();
    setupMatchTabs();

});


/* =========================================================
   HELPER FUNCTIONS
   ========================================================= */

function escapeHTML(value) {

    if (value === null || value === undefined) {
        return "";
    }

    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

}


function getTeamById(id) {

    return teams.find(function (team) {

        return Number(team.id) === Number(id);

    });

}


function getPlayerById(id) {

    return players.find(function (player) {

        return Number(player.id) === Number(id);

    });

}


function getMatchById(id) {

    return matches.find(function (match) {

        return Number(match.id) === Number(id);

    });

}


function getTeamName(teamId) {

    const team = getTeamById(teamId);

    if (team) {
        return team.name;
    }

    return "Unknown Team";

}


function formatDate(dateString) {

    if (!dateString) {
        return "";
    }

    const date =
        new Date(dateString + "T00:00:00");

    if (isNaN(date.getTime())) {
        return dateString;
    }

    return date.toLocaleDateString("en-GB", {

        day: "2-digit",
        month: "short",
        year: "numeric"

    });

}


/* =========================================================
   TEAM LOGOS
   ========================================================= */
function getTeamLogo(teamId) {

    const logos = {

        1: "images/teams/zambezi-fc.png",

        2: "images/teams/real-kafue.png",

        3: "images/teams/boarder-city.png",

        4: "images/teams/galaxy-fc.png",

        5: "images/teams/ngombe-bulls-fc.png",

        6: "images/teams/hungry-lions.png",

        7: "images/teams/g-donais.png",

        8: "images/teams/mulindi-united.png"

    };

    return logos[Number(teamId)] || "";

}
    

function getTeamInitials(teamName) {

    if (!teamName) {
        return "FC";
    }

    return teamName
        .split(" ")
        .map(function (word) {

            return word.charAt(0);

        })
        .join("")
        .substring(0, 3)
        .toUpperCase();

}


/* =========================================================
   TEAMS API
   ========================================================= */

async function loadTeams() {

    try {

        const response =
            await fetch("api/teams.php");

        if (!response.ok) {

            throw new Error(
                "Teams API error: " +
                response.status
            );

        }

        const result =
            await response.json();


        if (Array.isArray(result)) {

            teams = result;

        } else if (Array.isArray(result.teams)) {

            teams = result.teams;

        } else if (Array.isArray(result.data)) {

            teams = result.data;

        } else {

            teams = [];

        }


        console.log(
            "Teams loaded:",
            teams
        );


        displayTeams();
        updateQuickStats();

    }

    catch (error) {

        console.error(
            "Error loading teams:",
            error
        );

    }

}


/* =========================================================
   DISPLAY TEAMS
   ========================================================= */

function displayTeams() {

    const container =
        document.getElementById("teamsGrid") ||
        document.querySelector(".teams-grid") ||
        document.querySelector(".team-grid");


    if (!container) {

        console.warn(
            "Teams container not found."
        );

        return;

    }


    if (!teams.length) {

        container.innerHTML = `
            <p>No teams available.</p>
        `;

        return;

    }


    container.innerHTML =
        teams.map(function (team) {

            const logo =
                getTeamLogo(team.id);

            const initials =
                getTeamInitials(team.name);


            return `

                <div
                    class="team-card"
                    data-team-id="${escapeHTML(team.id)}"
                    style="cursor:pointer;"
                >

                    <div class="team-logo">

                        ${
                            logo

                            ?

                            `
                            <img
                                src="${logo}"
                                alt="${escapeHTML(team.name)} logo"
                                onerror="
                                    this.style.display='none';
                                    this.nextElementSibling.style.display='flex';
                                "
                            >

                            <span
                                class="team-logo-fallback"
                                style="display:none;"
                            >
                                ${escapeHTML(initials)}
                            </span>
                            `

                            :

                            `
                            <span
                                class="team-logo-fallback"
                                style="display:flex;"
                            >
                                ${escapeHTML(initials)}
                            </span>
                            `
                        }

                    </div>


                    <div class="team-card-info">

                        <h3>
                            ${escapeHTML(team.name)}
                        </h3>

                        <p>
                            ${escapeHTML(
                                team.short_name || ""
                            )}
                        </p>

                    </div>

                </div>

            `;

        }).join("");


    container
        .querySelectorAll(".team-card")
        .forEach(function (card) {

            card.addEventListener(
                "click",
                function () {

                    const teamId =
                        this.getAttribute(
                            "data-team-id"
                        );

                    showTeamProfile(teamId);

                }
            );

        });

}


/* =========================================================
   TEAM PROFILE
   ========================================================= */

function showTeamProfile(teamId) {

    const team =
        getTeamById(teamId);


    if (!team) {

        console.error(
            "Team not found:",
            teamId
        );

        return;

    }


    const modal =
        document.getElementById(
            "teamProfile"
        );


    const body =
        document.getElementById(
            "teamProfileBody"
        );


    if (!modal || !body) {

        console.log(
            "Team selected:",
            team
        );

        return;

    }


    const teamPlayers =
        players.filter(function (player) {

            return Number(player.team_id) ===
                   Number(team.id);

        });


    const logo =
        getTeamLogo(team.id);


    const initials =
        getTeamInitials(team.name);


    body.innerHTML = `

        <div class="rpl-team-profile">


            <div class="rpl-team-header">


                <div class="rpl-team-big-logo">

                    ${
                        logo

                        ?

                        `
                        <img
                            src="${logo}"
                            alt="${escapeHTML(team.name)} logo"
                            onerror="
                                this.style.display='none';
                                this.nextElementSibling.style.display='flex';
                            "
                        >

                        <span
                            style="display:none;"
                        >
                            ${escapeHTML(initials)}
                        </span>
                        `

                        :

                        `
                        <span style="display:flex;">
                            ${escapeHTML(initials)}
                        </span>
                        `
                    }

                </div>


                <div class="rpl-team-title">

                    <div class="section-label">
                        RPL TEAM
                    </div>

                    <h2>
                        ${escapeHTML(team.name)}
                    </h2>

                    <p>
                        ${escapeHTML(
                            team.short_name || ""
                        )}
                    </p>

                </div>


            </div>


            <div class="rpl-team-divider"></div>


            <div class="rpl-team-section-title">

                <span>
                    SQUAD
                </span>

                <strong>
                    ${teamPlayers.length} PLAYERS
                </strong>

            </div>


            <div class="rpl-team-players">

                ${
                    teamPlayers.length

                    ?

                    teamPlayers.map(function (player) {

                        return `

                            <div
                                class="rpl-team-player"
                                data-player-id="${escapeHTML(player.id)}"
                            >

                                <div class="rpl-player-number">

                                    #
                                    ${escapeHTML(
                                        player.jersey_number || "-"
                                    )}

                                </div>


                                <div class="rpl-player-info">

                                    <strong>
                                        ${escapeHTML(
                                            player.full_name
                                        )}
                                    </strong>

                                    <span>
                                        ${escapeHTML(
                                            player.position ||
                                            "Player"
                                        )}
                                    </span>

                                </div>

                            </div>

                        `;

                    }).join("")

                    :

                    `
                    <div class="rpl-no-players">

                        No players registered
                        for this team yet.

                    </div>
                    `
                }

            </div>


        </div>

    `;


    modal.style.display = "flex";


    const closeButton =
        modal.querySelector(
            ".modal-close"
        ) ||
        modal.querySelector(
            ".close"
        ) ||
        modal.querySelector(
            ".team-profile-close"
        );


    if (closeButton) {

        closeButton.onclick =
            function () {

                modal.style.display =
                    "none";

            };

    }

}


/* =========================================================
   PLAYERS API
   ========================================================= */

async function loadPlayers() {

    try {

        const response =
            await fetch("api/players.php");


        if (!response.ok) {

            throw new Error(
                "Players API error: " +
                response.status
            );

        }


        const result =
            await response.json();


        if (Array.isArray(result)) {

            players = result;

        } else if (
            Array.isArray(result.players)
        ) {

            players = result.players;

        } else if (
            Array.isArray(result.data)
        ) {

            players = result.data;

        } else {

            players = [];

        }


        console.log(
            "Players loaded:",
            players
        );


        displayPlayers();
        updateQuickStats();

    }

    catch (error) {

        console.error(
            "Error loading players:",
            error
        );

    }

}


/* =========================================================
   DISPLAY PLAYERS
   ========================================================= */

function displayPlayers(list) {

    const container =
        document.getElementById(
            "playersGrid"
        ) ||
        document.querySelector(
            ".players-grid"
        ) ||
        document.querySelector(
            ".player-grid"
        );


    if (!container) {

        console.warn(
            "Players container not found."
        );

        return;

    }


    const playerList =
        Array.isArray(list)
        ? list
        : players;


    if (!playerList.length) {

        container.innerHTML = `
            <p>No players available.</p>
        `;

        return;

    }


    container.innerHTML =
        playerList.map(function (player) {

            const teamName =
                player.team_name ||
                player.team ||
                getTeamName(
                    player.team_id
                );


            return `

                <div
                    class="player-card"
                    data-player-id="${escapeHTML(player.id)}"
                    style="cursor:pointer;"
                >

                    <div class="player-photo">

                        ${
                            player.photo

                            ?

                            `
                            <img
                                src="${escapeHTML(player.photo)}"
                                alt="${escapeHTML(player.full_name)}"
                                onerror="
                                    this.style.display='none';
                                "
                            >
                            `

                            :

                            `
                            <div class="player-photo-placeholder">

                                ${escapeHTML(
                                    (player.full_name || "P")
                                        .charAt(0)
                                        .toUpperCase()
                                )}

                            </div>
                            `
                        }

                    </div>


                    <div class="player-card-content">

                        <h3>
                            ${escapeHTML(
                                player.full_name
                            )}
                        </h3>

                        <p>
                            ${escapeHTML(
                                teamName
                            )}
                        </p>

                        <p>
                            ${escapeHTML(
                                player.position ||
                                "Player"
                            )}
                        </p>

                        <span class="jersey-number">

                            #
                            ${escapeHTML(
                                player.jersey_number ||
                                "-"
                            )}

                        </span>

                    </div>

                </div>

            `;

        }).join("");


    container
        .querySelectorAll(".player-card")
        .forEach(function (card) {

            card.addEventListener(
                "click",
                function () {

                    const playerId =
                        this.getAttribute(
                            "data-player-id"
                        );

                    showPlayerProfile(
                        playerId
                    );

                }
            );

        });

}


/* =========================================================
   PLAYER SEARCH
   ========================================================= */

function setupPlayerSearch() {

    const input =
        document.getElementById(
            "playerSearch"
        ) ||
        document.querySelector(
            ".player-search"
        );


    if (!input) {
        return;
    }


    input.addEventListener(
        "input",
        function () {

            const query =
                this.value
                    .trim()
                    .toLowerCase();


            if (!query) {

                displayPlayers(
                    players
                );

                return;

            }


            const filtered =
                players.filter(
                    function (player) {

                        const name =
                            (
                                player.full_name ||
                                ""
                            ).toLowerCase();


                        const position =
                            (
                                player.position ||
                                ""
                            ).toLowerCase();


                        const team =
                            (
                                player.team_name ||
                                player.team ||
                                getTeamName(
                                    player.team_id
                                )
                            ).toLowerCase();


                        return (
                            name.includes(query) ||
                            position.includes(query) ||
                            team.includes(query)
                        );

                    }
                );


            displayPlayers(
                filtered
            );

        }
    );

}


/* =========================================================
   MATCHES API
   ========================================================= */

async function loadMatches() {

    try {

        const response =
            await fetch("api/matches.php");

        if (!response.ok) {
            throw new Error(
                "Matches API error: " +
                response.status
            );
        }

        const result =
            await response.json();

        if (Array.isArray(result)) {
            matches = result;
        }
        else if (Array.isArray(result.matches)) {
            matches = result.matches;
        }
        else if (Array.isArray(result.data)) {
            matches = result.data;
        }
        else {
            matches = [];
        }

        console.log(
            "RPL Matches loaded:",
            matches
        );

        displayMatches();
        updateQuickStats();

    }
    catch (error) {

        console.error(
            "Error loading matches:",
            error
        );

        matches = [];

        displayMatches();
        updateQuickStats();

    }

}


/* =========================================================
   DISPLAY MATCHES
   ========================================================= */

function displayMatches() {

    const liveContainer =
        document.getElementById(
            "liveMatches"
        );


    const finishedContainer =
        document.getElementById(
            "finishedMatches"
        );


    const upcomingContainer =
        document.getElementById(
            "upcomingMatches"
        );


    if (
        !liveContainer &&
        !finishedContainer &&
        !upcomingContainer
    ) {

        console.warn(
            "Match containers not found."
        );

        return;

    }


    const live = [];
    const finished = [];
    const upcoming = [];


    matches.forEach(
        function (match) {

            const status =
                String(
                    match.status || ""
                ).toLowerCase();


            const hasScore =
                match.home_score !== null &&
                match.home_score !== undefined &&
                match.away_score !== null &&
                match.away_score !== undefined;


            if (
                status === "live" ||
                status === "ongoing" ||
                status === "in progress"
            ) {

                live.push(match);

            }

            else if (
                status === "finished" ||
                status === "completed"
            ) {

                finished.push(match);

            }

            else if (
                status === "upcoming" ||
                status === "scheduled" ||
                status === "pending"
            ) {

                upcoming.push(match);

            }

            else if (hasScore) {

                finished.push(match);

            }

            else {

                upcoming.push(match);

            }

        }
    );


    if (liveContainer) {

        liveContainer.innerHTML =
            live.length
            ?
            live.map(
                createMatchCard
            ).join("")
            :
            emptyMatchMessage(
                "No live matches at the moment."
            );

    }


    if (finishedContainer) {

        finishedContainer.innerHTML =
            finished.length
            ?
            finished.map(
                createMatchCard
            ).join("")
            :
            emptyMatchMessage(
                "No finished matches."
            );

    }


    if (upcomingContainer) {

        upcomingContainer.innerHTML =
            upcoming.length
            ?
            upcoming.map(
                createMatchCard
            ).join("")
            :
            emptyMatchMessage(
                "No upcoming matches."
            );

    }


    updateMatchCounters(
        live.length,
        finished.length,
        upcoming.length
    );


    
    attachMatchCardEvents();

loadMatchEventsForCards();

}


/* =========================================================
   MATCH CARD
   ========================================================= */

   function createMatchCard(match) {

    const homeTeam =
        match.home_team ||
        getTeamName(
            match.home_team_id
        );


    const awayTeam =
        match.away_team ||
        getTeamName(
            match.away_team_id
        );


    const status =
        String(
            match.status || ""
        ).toLowerCase();


    const hasScore =
        match.home_score !== null &&
        match.home_score !== undefined &&
        match.away_score !== null &&
        match.away_score !== undefined;


    let matchStatus =
        "UPCOMING";


    if (
        status === "live" ||
        status === "ongoing" ||
        status === "in progress"
    ) {

        matchStatus =
            "LIVE";

    }

    else if (
        hasScore ||
        status === "finished" ||
        status === "completed"
    ) {

        matchStatus =
            "FINISHED";

    }


    const homeLogo =
        getTeamLogo(
            match.home_team_id
        );


    const awayLogo =
        getTeamLogo(
            match.away_team_id
        );


    const homeInitials =
        getTeamInitials(
            homeTeam
        );


    const awayInitials =
        getTeamInitials(
            awayTeam
        );


    let homeLogoHTML = "";

    let awayLogoHTML = "";


    if (homeLogo) {

        homeLogoHTML = `

            <img
                src="${homeLogo}"
                alt="${escapeHTML(homeTeam)}"
                onerror="
                    this.style.display='none';
                    this.nextElementSibling.style.display='flex';
                "
            >

            <span
                class="match-logo-fallback"
                style="display:none;"
            >
                ${escapeHTML(homeInitials)}
            </span>

        `;

    }

    else {

        homeLogoHTML = `

            <span class="match-logo-fallback">

                ${escapeHTML(homeInitials)}

            </span>

        `;

    }


    if (awayLogo) {

        awayLogoHTML = `

            <img
                src="${awayLogo}"
                alt="${escapeHTML(awayTeam)}"
                onerror="
                    this.style.display='none';
                    this.nextElementSibling.style.display='flex';
                "
            >

            <span
                class="match-logo-fallback"
                style="display:none;"
            >
                ${escapeHTML(awayInitials)}
            </span>

        `;

    }

    else {

        awayLogoHTML = `

            <span class="match-logo-fallback">

                ${escapeHTML(awayInitials)}

            </span>

        `;

    }


    const scoreHTML =
        hasScore

        ?

        `

        <div class="match-score">

            <span>
                ${escapeHTML(
                    match.home_score
                )}
            </span>

            <strong>-</strong>

            <span>
                ${escapeHTML(
                    match.away_score
                )}
            </span>

        </div>

        `

        :

        `

        <div class="match-score">

            <strong>
                VS
            </strong>

        </div>

        `;


    return `

        <div
            class="match-card"
            data-match-id="${escapeHTML(match.id)}"
            style="cursor:pointer;"
        >


            <div class="match-status">

                ${escapeHTML(
                    matchStatus
                )}

            </div>


            <div class="match-date">

                ${escapeHTML(
                    formatDate(
                        match.match_date
                    )
                )}

                ${
                    match.match_time
                    ?
                    +
                    escapeHTML(
                        match.match_time
                    )
                    :
                    ""
                }

            </div>


            <div class="match-teams">


                <div class="match-team home-team">


                    <div class="match-team-logo">

                        ${homeLogoHTML}

                    </div>


                    <strong>

                        ${escapeHTML(
                            homeTeam
                        )}

                    </strong>


                </div>


                ${scoreHTML}


                <div class="match-team away-team">


                    <div class="match-team-logo">

                        ${awayLogoHTML}

                    </div>


                    <strong>

                        ${escapeHTML(
                            awayTeam
                        )}

                    </strong>


                </div>


            </div>


            ${
                match.venue

                ?

                `

                <div class="match-venue">

                    ${escapeHTML(
                        match.venue
                    )}

                </div>

                `

                :

                ""
            }


            <!-- MATCH EVENTS -->

            <div
                class="match-events"
                data-events-for="${escapeHTML(match.id)}"
                style="
                    margin-top:15px;
                    padding:10px;
                    border-top:1px solid rgba(255,255,255,0.12);
                "
            >

                <div
                    class="match-events-loading"
                    style="
                        font-size:13px;
                        opacity:0.65;
                        text-align:center;
                    "
                >
                    Loading match events...
                </div>

            </div>


        </div>

    `;

}


async function loadMatchEventsForCards() {

    const eventContainers =
        document.querySelectorAll(
            "[data-events-for]"
        );


    if (!eventContainers.length) {
        return;
    }


    eventContainers.forEach(
        async function (container) {

            const matchId =
                container.getAttribute(
                    "data-events-for"
                );


            if (!matchId) {
                return;
            }


            try {

                const response =
                    await fetch(
                        `api/match_events.php?match_id=${encodeURIComponent(matchId)}`
                    );


                if (!response.ok) {
                    throw new Error(
                        "HTTP " + response.status
                    );
                }


                const data =
                    await response.json();


                if (!data.success) {
                    throw new Error(
                        data.message ||
                        "Unable to load match events."
                    );
                }


                const goals =
                    Array.isArray(data.goals)
                    ? data.goals
                    : [];


                const cards =
                    Array.isArray(data.cards)
                    ? data.cards
                    : [];


                const substitutions =
                    Array.isArray(
                        data.substitutions
                    )
                    ? data.substitutions
                    : [];


                const events = [];


                /* ==========================
                   GOALS
                   ========================== */

                goals.forEach(
                    function (goal) {

                        events.push({

                            minute:
                                goal.minute !== null
                                ?
                                Number(
                                    goal.minute
                                )
                                :
                                9999,

                            html: `

                                <div
                                    class="rpl-event rpl-goal"
                                    style="
                                        display:flex;
                                        align-items:center;
                                        gap:8px;
                                        padding:5px 0;
                                    "
                                >

                                    <span>
                                        âš½
                                    </span>

                                    <strong>
                                        ${
                                            goal.minute !== null
                                            ?
                                            escapeHTML(
                                                goal.minute
                                            ) + "'"
                                            :
                                            ""
                                        }
                                    </strong>

                                    <span>
                                        ${escapeHTML(
                                            goal.player_name
                                        )}
                                    </span>

                                    <small
                                        style="
                                            opacity:0.6;
                                            margin-left:auto;
                                        "
                                    >
                                        ${escapeHTML(
                                            goal.team_name
                                        )}
                                    </small>

                                </div>

                            `

                        });

                    }
                );


                /* ==========================
                   CARDS
                   ========================== */

                cards.forEach(
                    function (card) {

                        const cardIcon =
                            String(
                                card.card_type
                            ).toLowerCase()
                            === "red"


                        events.push({

                            minute:
                                card.minute !== null
                                ?
                                Number(
                                    card.minute
                                )
                                :
                                9999,

                            html: `

                                <div
                                    class="rpl-event rpl-card-event"
                                    style="
                                        display:flex;
                                        align-items:center;
                                        gap:8px;
                                        padding:5px 0;
                                    "
                                >

                                    <span>
                                        ${cardIcon}
                                    </span>

                                    <strong>
                                        ${
                                            card.minute !== null
                                            ?
                                            escapeHTML(
                                                card.minute
                                            ) + "'"
                                            :
                                            ""
                                        }
                                    </strong>

                                    <span>
                                        ${escapeHTML(
                                            card.player_name
                                        )}
                                    </span>

                                    <small
                                        style="
                                            opacity:0.6;
                                            margin-left:auto;
                                        "
                                    >
                                        ${escapeHTML(
                                            card.team_name
                                        )}
                                    </small>

                                </div>

                            `

                        });

                    }
                );


                /* ==========================
                   SUBSTITUTIONS
                   ========================== */

                substitutions.forEach(
                    function (substitution) {

                        events.push({

                            minute:
                                substitution.minute !== null
                                ?
                                Number(
                                    substitution.minute
                                )
                                :
                                9999,

                            html: `

                                <div
                                    class="rpl-event rpl-substitution"
                                    style="
                                        display:flex;
                                        align-items:center;
                                        gap:8px;
                                        padding:5px 0;
                                    "
                                >

                                    <span>
                                        
                                    </span>

                                    <strong>
                                        ${
                                            substitution.minute !== null
                                            ?
                                            escapeHTML(
                                                substitution.minute
                                            ) + "'"
                                            :
                                            ""
                                        }
                                    </strong>

                                    <span>

                                        <span
                                            style="
                                                text-decoration:line-through;
                                                opacity:0.65;
                                            "
                                        >
                                            ${escapeHTML(
                                                substitution.player_out_name
                                            )}
                                        </span>

                                        <span>
                                            â†’
                                        </span>

                                        <strong>
                                            ${escapeHTML(
                                                substitution.player_in_name
                                            )}
                                        </strong>

                                    </span>

                                    <small
                                        style="
                                            opacity:0.6;
                                            margin-left:auto;
                                        "
                                    >
                                        ${escapeHTML(
                                            substitution.team_name
                                        )}
                                    </small>

                                </div>

                            `

                        });

                    }
                );


                /* ==========================
                   SORT EVENTS BY MINUTE
                   ========================== */

                events.sort(
                    function (a, b) {

                        return (
                            a.minute -
                            b.minute
                        );

                    }
                );


                /* ==========================
                   DISPLAY
                   ========================== */

                if (!events.length) {

                    container.innerHTML = `

                        <div
                            style="
                                text-align:center;
                                font-size:13px;
                                opacity:0.55;
                                padding:6px;
                            "
                        >
                            No match events yet.
                        </div>

                    `;

                    return;

                }


                container.innerHTML = `

                    <div
                        class="match-events-title"
                        style="
                            font-weight:700;
                            font-size:13px;
                            margin-bottom:6px;
                            text-align:left;
                        "
                    >
                        MATCH EVENTS
                    </div>

                    ${events
                        .map(
                            function (event) {
                                return event.html;
                            }
                        )
                        .join("")
                    }

                `;


            } catch (error) {

                console.error(
                    "Match events error:",
                    error
                );


                container.innerHTML = `

                    <div
                        style="
                            text-align:center;
                            font-size:12px;
                            opacity:0.5;
                        "
                    >
                        Match events unavailable.
                    </div>

                `;

            }

        }
    );

}


/* =========================================================
   EMPTY MATCH MESSAGE
   ========================================================= */

function emptyMatchMessage(message) {

    return `

        <div class="match-empty">

            <p>
                ${escapeHTML(
                    message
                )}
            </p>

        </div>

    `;

}


/* =========================================================
   MATCH CLICK EVENTS
   ========================================================= */

function attachMatchCardEvents() {

    document
        .querySelectorAll(
            ".match-card"
        )
        .forEach(function (card) {


            card.addEventListener(
                "click",
                function () {


                    const matchId =
                        this.getAttribute(
                            "data-match-id"
                        );


                    if (!matchId) {

                        console.error(
                            "Match card has no match ID."
                        );

                        return;

                    }


                    showMatchDetails(
                        matchId
                    );

                }
            );


        });

}


/* =========================================================
   MATCH DETAILS
   ========================================================= */

function showMatchDetails(matchId) {

    const match =
        getMatchById(
            matchId
        );


    if (!match) {

        console.error(
            "Match not found:",
            matchId
        );

        return;

    }


    const homeTeam =
        match.home_team ||
        getTeamName(
            match.home_team_id
        );


    const awayTeam =
        match.away_team ||
        getTeamName(
            match.away_team_id
        );


    const oldModal =
        document.getElementById(
            "matchDetailsModal"
        );


    if (oldModal) {

        oldModal.remove();

    }


    const modal =
        document.createElement(
            "div"
        );


    modal.id =
        "matchDetailsModal";


    modal.className =
        "match-details-modal";


    modal.innerHTML = `

        <div
            class="match-details-overlay"
        >


            <div
                class="match-details-content"
            >


                <button
                    type="button"
                    class="match-details-close"
                >
                    ×
                </button>


                <div class="section-label">

                    MATCH DETAILS

                </div>


                <h2>

                    ${escapeHTML(
                        homeTeam
                    )}

                    vs

                    ${escapeHTML(
                        awayTeam
                    )}

                </h2>


                <div
                    class="match-details-score"
                >

                    <span>

                        ${
                            match.home_score !== null &&
                            match.home_score !== undefined
                            ?
                            escapeHTML(
                                match.home_score
                            )
                            :
                            "-"
                        }

                    </span>


                    <strong>
                        :
                    </strong>


                    <span>

                        ${
                            match.away_score !== null &&
                            match.away_score !== undefined
                            ?
                            escapeHTML(
                                match.away_score
                            )
                            :
                            "-"
                        }

                    </span>


                </div>


                <div
                    class="match-details-info"
                >


                    <p>

                        <strong>
                            Date:
                        </strong>

                        ${escapeHTML(
                            formatDate(
                                match.match_date
                            )
                        )}

                    </p>


                    ${
                        match.match_time

                        ?

                        `

                        <p>

                            <strong>
                                Time:
                            </strong>

                            ${escapeHTML(
                                match.match_time
                            )}

                        </p>

                        `

                        :

                        ""
                    }


                    ${
                        match.venue

                        ?

                        `

                        <p>

                            <strong>
                                Venue:
                            </strong>

                            ${escapeHTML(
                                match.venue
                            )}

                        </p>

                        `

                        :

                        ""
                    }


                    <p>

                        <strong>
                            Status:
                        </strong>

                        ${escapeHTML(
                            match.status ||
                            "Unknown"
                        )}

                    </p>


                    ${
                        match.matchday

                        ?

                        `

                        <p>

                            <strong>
                                Matchday:
                            </strong>

                            ${escapeHTML(
                                match.matchday
                            )}

                        </p>

                        `

                        :

                        ""
                    }


                </div>


            </div>


        </div>

    `;


    document.body.appendChild(
        modal
    );


    const closeButton =
        modal.querySelector(
            ".match-details-close"
        );


    const overlay =
        modal.querySelector(
            ".match-details-overlay"
        );


    closeButton.addEventListener(
        "click",
        function () {

            modal.remove();

        }
    );


    overlay.addEventListener(
        "click",
        function (event) {

            if (
                event.target === overlay
            ) {

                modal.remove();

            }

        }
    );

}


/* =========================================================
   MATCH TABS
   ========================================================= */

function setupMatchTabs() {

    const tabs =
        document.querySelectorAll(
            ".match-tabs .tab"
        );


    if (!tabs.length) {
        return;
    }


    tabs.forEach(function (tab) {


        tab.addEventListener(
            "click",
            function () {


                tabs.forEach(
                    function (item) {

                        item.classList.remove(
                            "active"
                        );

                    }
                );


                this.classList.add(
                    "active"
                );


                const tabName =
                    this.getAttribute(
                        "data-tab"
                    );


                if (!tabName) {
                    return;
                }


                const containers = {

                    live:
                        document.getElementById(
                            "liveMatches"
                        ),

                    finished:
                        document.getElementById(
                            "finishedMatches"
                        ),

                    upcoming:
                        document.getElementById(
                            "upcomingMatches"
                        )

                };


                Object.keys(
                    containers
                ).forEach(function (key) {

                    if (
                        containers[key]
                    ) {

                        containers[key].style.display =
                            "none";

                    }

                });


                if (
                    containers[tabName]
                ) {

                    containers[tabName].style.display =
                        "block";

                }


            }
        );


    });

}


/* =========================================================
   MATCH COUNTERS
   ========================================================= */

function updateMatchCounters(
    live,
    finished,
    upcoming
) {


    const liveCount =
        document.getElementById(
            "liveCount"
        );


    const finishedCount =
        document.getElementById(
            "finishedCount"
        );


    const upcomingCount =
        document.getElementById(
            "upcomingCount"
        );


    if (liveCount) {

        liveCount.textContent =
            live;

    }


    if (finishedCount) {

        finishedCount.textContent =
            finished;

    }


    if (upcomingCount) {

        upcomingCount.textContent =
            upcoming;

    }

}


/* =========================================================
   STANDINGS API
   ========================================================= */

async function loadStandings() {

    try {

        const response =
            await fetch(
                "api/standings.php"
            );


        if (!response.ok) {

            throw new Error(
                "Standings API error: " +
                response.status
            );

        }


        const result =
            await response.json();


        if (Array.isArray(result)) {

            standings = result;

        } else if (
            Array.isArray(
                result.standings
            )
        ) {

            standings =
                result.standings;

        } else if (
            Array.isArray(
                result.data
            )
        ) {

            standings =
                result.data;

        } else {

            standings = [];

        }


        console.log(
            "Standings loaded:",
            standings
        );


        displayStandings();

    }

    catch (error) {

        console.error(
            "Error loading standings:",
            error
        );

    }

}


/* =========================================================
   DISPLAY STANDINGS
   ========================================================= */

function displayStandings() {

    const body =
        document.getElementById(
            "standingsBody"
        );


    if (!body) {

        console.warn(
            "Standings body not found."
        );

        return;

    }


    body.innerHTML =
        standings.map(
            function (team, index) {


                const name =
                    team.team_name ||
                    team.name ||
                    team.team ||
                    "Unknown";


                const played =
                    Number(
                        team.played ??
                        team.matches_played ??
                        team.p ??
                        0
                    );


                const wins =
                    Number(
                        team.wins ??
                        team.w ??
                        0
                    );


                const draws =
                    Number(
                        team.draws ??
                        team.d ??
                        0
                    );


                const losses =
                    Number(
                        team.losses ??
                        team.l ??
                        0
                    );


                const gd =
                    Number(
                        team.goal_difference ??
                        team.gd ??
                        0
                    );


                const points =
                    Number(
                        team.points ??
                        team.pts ??
                        0
                    );


                return `

                    <tr>


                        <td>
                            ${index + 1}
                        </td>


                        <td>

                            <strong>
                                ${escapeHTML(
                                    name
                                )}
                            </strong>

                        </td>


                        <td>
                            ${played}
                        </td>


                        <td>
                            ${wins}
                        </td>


                        <td>
                            ${draws}
                        </td>


                        <td>
                            ${losses}
                        </td>


                        <td>
                            ${gd}
                        </td>


                        <td>

                            <strong>
                                ${points}
                            </strong>

                        </td>


                    </tr>

                `;

            }
        ).join("");

}


/* =========================================================
   GOALKEEPERS API
   ========================================================= */

async function loadGoalkeepers() {

    try {

        const response =
            await fetch(
                "api/goalkeepers.php"
            );


        if (!response.ok) {

            throw new Error(
                "Goalkeepers API error: " +
                response.status
            );

        }


        const result =
            await response.json();


        if (Array.isArray(result)) {

            goalkeepers = result;

        } else if (
            Array.isArray(
                result.goalkeepers
            )
        ) {

            goalkeepers =
                result.goalkeepers;

        } else if (
            Array.isArray(
                result.data
            )
        ) {

            goalkeepers =
                result.data;

        } else {

            goalkeepers = [];

        }


        console.log(
            "Goalkeepers loaded:",
            goalkeepers
        );


        displayGoalkeepers();

    }

    catch (error) {

        console.error(
            "Error loading goalkeepers:",
            error
        );

    }

}


/* =========================================================
   DISPLAY GOALKEEPERS
   ========================================================= */

function displayGoalkeepers() {

    const body =
        document.getElementById(
            "goalkeepersBody"
        );


    if (!body) {

        console.warn(
            "Goalkeepers body not found."
        );

        return;

    }


    body.innerHTML =
        goalkeepers.map(
            function (keeper, index) {


                const shots =
                    Number(
                        keeper.shots_on_target_faced ||
                        0
                    );


                const saves =
                    Number(
                        keeper.saves ||
                        0
                    );


                let savePercentage =
                    0;


                if (shots > 0) {

                    savePercentage =
                        (
                            saves /
                            shots
                        ) *
                        100;

                }


                return `

                    <tr>


                        <td>
                            ${index + 1}
                        </td>


                        <td>

                            <strong>

                                ${escapeHTML(
                                    keeper.full_name ||
                                    keeper.player_name ||
                                    "Unknown"
                                )}

                            </strong>

                        </td>


                        <td>

                            ${escapeHTML(
                                keeper.team_name ||
                                keeper.team ||
                                "-"
                            )}

                        </td>


                        <td>

                            ${Number(
                                keeper.appearances ||
                                keeper.apps ||
                                0
                            )}

                        </td>


                        <td>

                            ${Number(
                                keeper.minutes_played ||
                                keeper.minutes ||
                                0
                            )}

                        </td>


                        <td>
                            ${saves}
                        </td>


                        <td>

                            ${savePercentage.toFixed(1)}%

                        </td>


                        <td>

                            ${Number(
                                keeper.clean_sheets ||
                                0
                            )}

                        </td>


                        <td>

                            ${Number(
                                keeper.goals_conceded ||
                                0
                            )}

                        </td>


                        <td>

                            ${Number(
                                keeper.goals_prevented ||
                                0
                            ).toFixed(2)}

                        </td>


                    </tr>

                `;

            }
        ).join("");

}


/* =========================================================
   QUICK STATISTICS
   ========================================================= */

function updateQuickStats() {


    const teamCount =
        document.getElementById(
            "teamCount"
        );


    const playerCount =
        document.getElementById(
            "playerCount"
        );


    const matchCount =
        document.getElementById(
            "matchCount"
        ) ||
        document.getElementById(
            "matchesPlayed"
        );


    const goalCount =
        document.getElementById(
            "goalCount"
        );


    if (teamCount) {

        teamCount.textContent =
            teams.length;

    }


    if (playerCount) {

        playerCount.textContent =
            players.length;

    }


    if (matchCount) {


        const completed =
            matches.filter(
                function (match) {

                    return (
                        match.home_score !== null &&
                        match.home_score !== undefined &&
                        match.away_score !== null &&
                        match.away_score !== undefined
                    );

                }
            ).length;


        matchCount.textContent =
            completed;

    }


    if (goalCount) {


        let totalGoals =
            0;


        matches.forEach(
            function (match) {


                if (
                    match.home_score !== null &&
                    match.home_score !== undefined
                ) {

                    totalGoals +=
                        Number(
                            match.home_score
                        );

                }


                if (
                    match.away_score !== null &&
                    match.away_score !== undefined
                ) {

                    totalGoals +=
                        Number(
                            match.away_score
                        );

                }

            }
        );


        goalCount.textContent =
            totalGoals;

    }

}


/* =========================================================
   NAVIGATION
   ========================================================= */

function setupNavigation() {

    document
        .querySelectorAll(
            "a[href^='#']"
        )
        .forEach(
            function (link) {


                link.addEventListener(
                    "click",
                    function (event) {


                        const href =
                            this.getAttribute(
                                "href"
                            );


                        if (
                            !href ||
                            href === "#"
                        ) {

                            return;

                        }


                        const target =
                            document.querySelector(
                                href
                            );


                        if (!target) {
                            return;
                        }


                        event.preventDefault();


                        target.scrollIntoView({

                            behavior: "smooth",

                            block: "start"

                        });


                    }
                );


            }
        );

}


/* =========================================================
   CLOSE NORMAL MODALS
   ========================================================= */

document.addEventListener(
    "click",
    function (event) {


        if (
            event.target.classList.contains(
                "modal-close"
            )
        ) {


            const modal =
                event.target.closest(
                    ".modal"
                );


            if (modal) {

                modal.style.display =
                    "none";

            }

        }


    }
);


/* =========================================================
   GLOBAL RPL OBJECT
   ========================================================= */

window.RPL = {

    get teams() {
        return teams;
    },

    get players() {
        return players;
    },

    get matches() {
        return matches;
    },

    get standings() {
        return standings;
    },

    get goalkeepers() {
        return goalkeepers;
    },

    loadTeams:
        loadTeams,

    loadPlayers:
        loadPlayers,

    loadMatches:
        loadMatches,

    loadStandings:
        loadStandings,

    loadGoalkeepers:
        loadGoalkeepers,

    showMatchDetails:
        showMatchDetails,

    showPlayerProfile:
        showPlayerProfile,

    showTeamProfile:
        showTeamProfile

};


console.log(
    "RPL JavaScript initialized successfully."
);


/* =========================================================
   RPL NEWS & UPDATES
   ========================================================= */

async function loadNews() {

    try {

        const response = await fetch("api/news.php");

        if (!response.ok) {
            throw new Error("News API request failed");
        }

        const data = await response.json();

        console.log("RPL News loaded:", data);

        if (!data.success) {
            throw new Error(data.message || "Unable to load news");
        }

        displayNews(data.news);

    } catch (error) {

        console.error("News loading error:", error);

        const newsContainer =
            document.getElementById("newsContainer");

        if (newsContainer) {

            newsContainer.innerHTML = `
                <div class="news-error">
                    Unable to load RPL news at the moment.
                </div>
            `;
        }
    }
}


/* =========================================================
   DISPLAY NEWS
   ========================================================= */

function displayNews(news) {

    const container =
        document.getElementById("newsContainer");

    if (!container) {

        console.warn(
            "newsContainer not found. News section has not been connected yet."
        );

        return;
    }

    if (!news || news.length === 0) {

        container.innerHTML = `
            <div class="news-empty">
                <h3>No updates yet</h3>
                <p>
                    RPL news and announcements will appear here.
                </p>
            </div>
        `;

        return;
    }

    container.innerHTML = news.map(article => {

        const date = article.created_at
            ? new Date(article.created_at).toLocaleDateString(
                "en-GB",
                {
                    day: "2-digit",
                    month: "short",
                    year: "numeric"
                }
            )
            : "";

        const image = article.image
            ? `<img src="${article.image}" alt="${escapeNewsText(article.title)}">`
            : "";

        return `
            <article class="rpl-news-card">

                ${image}

                <div class="rpl-news-content">

                    <div class="rpl-news-meta">
                        ${date}
                        ${article.author ? escapeNewsText(article.author) : ""}
                    </div>

                    <h3>
                        ${escapeNewsText(article.title)}
                    </h3>

                    <p>
                        ${escapeNewsText(article.content)}
                    </p>

                </div>

            </article>
        `;

    }).join("");
}


/* =========================================================
   ESCAPE NEWS TEXT
   ========================================================= */

function escapeNewsText(text) {

    if (text === null || text === undefined) {
        return "";
    }

    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}


/* =========================================================
   START NEWS
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    loadNews();

});


/* =========================================================
   RPL PLAYER PROFILE
   ========================================================= */

async function showPlayerProfile(playerId) {

    console.log(
        "Opening player profile:",
        playerId
    );


    /* ---------------------------------------------------------
       REMOVE EXISTING PROFILE MODAL
       --------------------------------------------------------- */

    const oldModal =
        document.getElementById(
            "playerProfileModal"
        );

    if (oldModal) {
        oldModal.remove();
    }


    /* ---------------------------------------------------------
       LOADING MODAL
       --------------------------------------------------------- */

    const loadingModal =
        document.createElement("div");

    loadingModal.id =
        "playerProfileModal";

    loadingModal.innerHTML = `

        <div class="player-profile-overlay">

            <div class="player-profile-modal">

                <button
                    class="player-profile-close"
                    onclick="closePlayerProfile()"
                >
                    ×
                </button>

                <div class="player-profile-loading">

                    <div class="player-profile-spinner">
                        âš½
                    </div>

                    <h2>
                        Loading Player Profile...
                    </h2>

                    <p>
                        Please wait.
                    </p>

                </div>

            </div>

        </div>

    `;


    document.body.appendChild(
        loadingModal
    );


    /* ---------------------------------------------------------
       LOAD PLAYER DATA
       --------------------------------------------------------- */

    try {

        const response =
            await fetch(
                `api/player_profile.php?id=${encodeURIComponent(playerId)}`
            );


        if (!response.ok) {

            throw new Error(
                "Unable to contact player profile API."
            );

        }


        const data =
            await response.json();


        console.log(
            "Player profile loaded:",
            data
        );


        if (!data.success) {

            throw new Error(
                data.message ||
                "Player profile could not be loaded."
            );

        }


        const player =
            data.player;

        const stats =
            data.stats || {};

        const goalkeeper =
            data.goalkeeper_stats;

        const matches =
            data.matches || [];


        /* -----------------------------------------------------
           PLAYER PHOTO
           ----------------------------------------------------- */

        let photoHTML = "";

        if (player.photo) {

            photoHTML = `

                <img
                    src="${escapeHTML(player.photo)}"
                    alt="${escapeHTML(player.full_name)}"
                    onerror="
                        this.style.display='none';
                        this.nextElementSibling.style.display='flex';
                    "
                >

                <div
                    class="player-profile-photo-fallback"
                    style="display:none;"
                >
                    ${escapeHTML(
                        getTeamInitials(
                            player.full_name
                        )
                    )}
                </div>

            `;

        } else {

            photoHTML = `

                <div class="player-profile-photo-fallback">

                    ${escapeHTML(
                        getTeamInitials(
                            player.full_name
                        )
                    )}

                </div>

            `;

        }


        /* -----------------------------------------------------
           GOALKEEPER SECTION
           ----------------------------------------------------- */

        let goalkeeperHTML = "";


        if (goalkeeper) {

            goalkeeperHTML = `

                <div class="player-profile-section">

                    <h2>
                         Goalkeeper Statistics
                    </h2>


                    <div class="player-profile-stats-grid">

                        <div class="profile-stat">

                            <strong>
                                ${escapeHTML(
                                    goalkeeper.appearances
                                )}
                            </strong>

                            <span>
                                Apps
                            </span>

                        </div>


                        <div class="profile-stat">

                            <strong>
                                ${escapeHTML(
                                    goalkeeper.minutes
                                )}
                            </strong>

                            <span>
                                Minutes
                            </span>

                        </div>


                        <div class="profile-stat">

                            <strong>
                                ${escapeHTML(
                                    goalkeeper.saves
                                )}
                            </strong>

                            <span>
                                Saves
                            </span>

                        </div>


                        <div class="profile-stat">

                            <strong>
                                ${escapeHTML(
                                    goalkeeper.save_percentage
                                )}%
                            </strong>

                            <span>
                                Save %
                            </span>

                        </div>


                        <div class="profile-stat">

                            <strong>
                                ${escapeHTML(
                                    goalkeeper.clean_sheets
                                )}
                            </strong>

                            <span>
                                Clean Sheets
                            </span>

                        </div>


                        <div class="profile-stat">

                            <strong>
                                ${escapeHTML(
                                    goalkeeper.goals_conceded
                                )}
                            </strong>

                            <span>
                                Goals Conceded
                            </span>

                        </div>


                        <div class="profile-stat">

                            <strong>
                                ${escapeHTML(
                                    goalkeeper.goals_prevented
                                )}
                            </strong>

                            <span>
                                Goals Prevented
                            </span>

                        </div>


                        <div class="profile-stat">

                            <strong>
                                ${escapeHTML(
                                    goalkeeper.penalties_saved
                                )}
                            </strong>

                            <span>
                                Penalties Saved
                            </span>

                        </div>

                    </div>

                </div>

            `;

        }


        /* -----------------------------------------------------
           MATCH HISTORY
           ----------------------------------------------------- */

        let matchHistoryHTML = "";


        if (matches.length > 0) {

            matchHistoryHTML = matches.map(
                function (match) {

                    const score =
                        match.home_score !== null &&
                        match.away_score !== null

                        ?

                        `${escapeHTML(
                            match.home_score
                        )} - ${escapeHTML(
                            match.away_score
                        )}`

                        :

                        "VS";


                    return `

                        <div class="player-profile-match">

                            <div>

                                <strong>

                                    ${escapeHTML(
                                        match.home_team
                                    )}

                                    <span>
                                        vs
                                    </span>

                                    ${escapeHTML(
                                        match.away_team
                                    )}

                                </strong>

                                <small>

                                    ${escapeHTML(
                                        formatDate(
                                            match.match_date
                                        )
                                    )}

                                </small>

                            </div>


                            <div class="player-profile-match-score">

                                ${score}

                            </div>

                        </div>

                    `;

                }
            ).join("");

        } else {

            matchHistoryHTML = `

                <div class="player-profile-empty">

                    No match history available yet.

                </div>

            `;

        }


        /* -----------------------------------------------------
           MAIN PROFILE
           ----------------------------------------------------- */

        loadingModal.innerHTML = `

            <div class="player-profile-overlay">

                <div class="player-profile-modal">

                    <button
                        class="player-profile-close"
                        onclick="closePlayerProfile()"
                    >
                        ×
                    </button>


                    <div class="player-profile-header">


                        <div class="player-profile-photo">

                            ${photoHTML}

                        </div>


                        <div class="player-profile-main-info">

                            <span class="player-profile-label">

                                RPL PLAYER

                            </span>


                            <h1>

                                ${escapeHTML(
                                    player.full_name
                                )}

                            </h1>


                            <p class="player-profile-team">

                                ${escapeHTML(
                                    player.team_name
                                )}

                            </p>


                            <div class="player-profile-tags">

                                <span>

                                    ${escapeHTML(
                                        player.position
                                    )}

                                </span>


                                <span>

                                    #

                                    ${escapeHTML(
                                        player.jersey_number
                                    )}

                                </span>


                                <span>

                                    ${escapeHTML(
                                        player.registration_number
                                    )}

                                </span>

                            </div>

                        </div>

                    </div>


                    <div class="player-profile-section">

    <h2>
         Season Statistics
    </h2>

    <!-- RPL PLAYER RATING -->
    <div
        style="
            width:100%;
            box-sizing:border-box;
            margin:20px 0;
            padding:20px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            border-radius:20px;
            background:linear-gradient(135deg, #ffd700, #ff9800);
            border:3px solid #ffffff;
            box-shadow:0 8px 25px rgba(0,0,0,0.35);
            color:#111111;
        "
    >

        <div
            style="
                display:flex;
                align-items:center;
                gap:12px;
            "
        >

            <div
                style="
                    font-size:36px;
                "
            >
                ⭐

            </div>

            <div>

                <div
                    style="
                        font-size:16px;
                        font-weight:900;
                        letter-spacing:1px;
                        color:#111111;
                    "
                >
                    RPL PLAYER RATING
                </div>

                <div
                    style="
                        font-size:12px;
                        font-weight:700;
                        margin-top:4px;
                        color:#111111;
                    "
                >
                    Current Season Performance
                </div>

            </div>

        </div>


        <div
            style="
                font-size:44px;
                font-weight:900;
                color:#111111;
                white-space:nowrap;
            "
        >
            ${escapeHTML(
                stats.player_rating || 0
            )}

            <span
                style="
                    font-size:15px;
                    font-weight:900;
                "
            >
                /10
            </span>

        </div>

    </div>


    <!-- NORMAL SEASON STATISTICS -->

    <div class="player-profile-stats-grid">

        <div class="profile-stat">

            <strong>
                ${escapeHTML(
                    stats.appearances || 0
                )}
            </strong>

            <span>
                Appearances
            </span>

        </div>


        <div class="profile-stat">

            <strong>
                ${escapeHTML(
                    stats.goals || 0
                )}
            </strong>

            <span>
                Goals
            </span>

        </div>


        <div class="profile-stat">

            <strong>
                ${escapeHTML(
                    stats.minutes || 0
                )}
            </strong>

            <span>
                Minutes
            </span>

        </div>


        <div class="profile-stat">

            <strong>
                ${escapeHTML(
                    stats.yellow_cards || 0
                )}
            </strong>

            <span>
                 Yellow
            </span>

        </div>


        <div class="profile-stat">

            <strong>
                ${escapeHTML(
                    stats.red_cards || 0
                )}
            </strong>

            <span>
                 Red
            </span>

        </div>

    </div>

</div>


${goalkeeperHTML}


<div class="player-profile-section">

    <h2>
         Match History
    </h2>

    <div class="player-profile-matches">

        ${matchHistoryHTML}

    </div>

</div>

            </div>

        `;


        /* -----------------------------------------------------
           CLOSE WHEN CLICKING OUTSIDE
           ----------------------------------------------------- */

        const overlay =
            loadingModal.querySelector(
                ".player-profile-overlay"
            );


        if (overlay) {

            overlay.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target === overlay
                    ) {

                        closePlayerProfile();

                    }

                }
            );

        }


    } catch (error) {

        console.error(
            "Player profile error:",
            error
        );


        loadingModal.innerHTML = `

            <div class="player-profile-overlay">

                <div class="player-profile-modal">

                    <button
                        class="player-profile-close"
                        onclick="closePlayerProfile()"
                    >
                        ×
                    </button>


                    <div class="player-profile-loading">

                        <h2>
                            ×¸ Profile Error
                        </h2>

                        <p>
                            ${escapeHTML(
                                error.message
                            )}
                        </p>

                    </div>

                </div>

            </div>

        `;

    }

}


/* =========================================================
   CLOSE PLAYER PROFILE
   ========================================================= */

function closePlayerProfile() {

    const modal =
        document.getElementById(
            "playerProfileModal"
        );


    if (modal) {

        modal.remove();

    }

}


/* =========================================================
   RPL TOP SCORERS
   ========================================================= */

async function loadTopScorers() {

    try {

        const response = await fetch(
            "api/top_scorers.php"
        );

        if (!response.ok) {

            throw new Error(
                "Top Scorers API error: " +
                response.status
            );

        }

        const result = await response.json();

        console.log(
            "RPL Top Scorers loaded:",
            result
        );

        const body =
            document.getElementById(
                "topScorersBody"
            );

        if (!body) {

            console.warn(
                "topScorersBody not found."
            );

            return;
        }

        if (
            !result.success ||
            !Array.isArray(result.scorers) ||
            result.scorers.length === 0
        ) {

            body.innerHTML = `
                <tr>
                    <td colspan="4">
                        No goals recorded
                    </td>
                </tr>
            `;

            return;
        }

        body.innerHTML =
            result.scorers.map(
                function (player, index) {

                    return `
                        <tr>

                            <td>
                                ${index + 1}
                            </td>

                            <td>
                                <strong>
                                    ${escapeHTML(
                                        player.full_name
                                    )}
                                </strong>
                            </td>

                            <td>
                                ${escapeHTML(
                                    player.team_name
                                )}
                            </td>

                            <td>
                                <strong>
                                    ${player.goals}
                                </strong>
                            </td>

                        </tr>
                    `;

                }
            ).join("");

    }

    catch (error) {

        console.error(
            "Error loading top scorers:",
            error
        );

    }

}

/* =========================================================
   RPL PLAYER STATISTICS
   LIVE MATCH PERFORMANCE DATA
   ========================================================= */

async function loadPlayerStatistics() {

    try {

        const response = await fetch(
            "api/player_performances.php"
        );

        if (!response.ok) {

            throw new Error(
                "Player Performances API error: " +
                response.status
            );

        }

        const result = await response.json();

        console.log(
            "RPL Player Performances loaded:",
            result
        );

        const body =
            document.getElementById(
                "playerStatisticsBody"
            );

        if (!body) {

            console.warn(
                "playerStatisticsBody not found."
            );

            return;

        }


        if (
            !result.success ||
            !Array.isArray(result.players)
        ) {

            body.innerHTML = `
                <tr>
                    <td colspan="9">
                        No player statistics available
                    </td>
                </tr>
            `;

            return;

        }


        /*
        =====================================================
        ONLY SHOW OUTFIELD PLAYERS
        =====================================================
        */

        const outfieldPlayers =
            result.players.filter(function (player) {

                return (
                    !player.position ||
                    player.position.toLowerCase() !==
                    "goalkeeper"
                );

            });


        if (outfieldPlayers.length === 0) {

            body.innerHTML = `
                <tr>
                    <td colspan="9">
                        No outfield players available
                    </td>
                </tr>
            `;

            return;

        }


        /*
        =====================================================
        BUILD TABLE
        =====================================================
        */

        body.innerHTML =
            outfieldPlayers.map(
                function (player, index) {

                    return `

                        <tr
                            data-player-id="${escapeHTML(
                                String(player.player_id)
                            )}"
                        >

                            <td>
                                ${index + 1}
                            </td>

                            <td>
                                <strong>
                                    ${escapeHTML(
                                        player.full_name
                                    )}
                                </strong>
                            </td>

                            <td>
                                ${escapeHTML(
                                    player.team_name || "-"
                                )}
                            </td>

                            <td>
                                ${player.appearances ?? 0}
                            </td>

                            <td>
                                <strong>
                                    ${player.goals ?? 0}
                                </strong>
                            </td>

                            <td>
                                ${player.minutes ?? 0}
                            </td>

                            <td>
                                ${player.yellow_cards ?? 0}
                            </td>

                            <td>
                                ${player.red_cards ?? 0}
                            </td>

                            <td>
                                <strong>
                                    ${Number(
                                        player.player_rating ?? 0
                                    ).toFixed(1)}
                                </strong>
                            </td>

                        </tr>

                    `;

                }
            ).join("");


    } catch (error) {

        console.error(
            "Error loading player performances:",
            error
        );

    }

}