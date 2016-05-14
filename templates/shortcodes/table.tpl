{% for ranking in rankings %}
{% if ranking.league.link %}
<h2><a href="/tabelle/{{ ranking.league.link }}">{{ ranking.league.name }} <i class="fa fa-angle-double-right"></i></a></h2>
{% else %}
<h2>{{ ranking.league.name }}</h2>
{% endif %}
<a name="{{ ranking.league.code }}"></a>
<table class="table table-striped kkl-table sortable">
    <thead>
      <tr>
        <th class="position">Platz</th>
        <th class="team">Team</th>
        <th class="games">Spiele</th>
        <th class="">g/u/v</th>
        <th class="win hidden-xs">g.</th>
        <th class="draw hidden-xs">u.</th>
        <th class="loose hidden-xs">v.</th>
        <th class="set_rate">
          <span class="visible-xs">diff</span>
          <span class="hidden-xs">Satzdiff.</span>
        </th>
        <th class="set hidden-xs">Sätze</th>
        <th class="goal_rate hidden-xs">Tordiff.</th>
        <th class="goals hidden-xs">Tore</th>
        <th class="points">Punkte</th>
      </tr>
    </thead>
      <tbody>
      {% for rank in ranking.ranks %}
      <tr>
        <td>{% if not rank.shared_rank %}{{ rank.position }}{% endif %}</td>
        <td><a href="/team/{{ rank.team.link }}" target="_self">{{ rank.team.name }}</a>{% if rank.team.properties['current_league_winner']%} (M){% endif %}{% if rank.team.properties['current_cup_winner']%} (P){% endif %}</td>
        <td>{{ rank.games }}</td>
        <td class="visible-xs">{{ rank.wins }}/{{ rank.draws }}/{{ rank.losses }}</td>
        <td class="hidden-xs">{{ rank.wins }}</td>
        <td class="hidden-xs">{{ rank.draws }}</td>
        <td class="hidden-xs">{{ rank.losses }}</td>
        <td class="">{{ rank.gameDiff }}</td>
        <td class="hidden-xs">{{ rank.gamesFor }}:{{ rank.gamesAgainst }}</td>
        <td class="hidden-xs">{{ rank.goalDiff }}</td>
        <td class="hidden-xs">{{ rank.goalsFor }}:{{ rank.goalsAgainst }}</td>
        <td><strong>{{ rank.score }}</strong></td>
      </tr>
      {% endfor %}
    </tbody>
</table>
{% endfor %}