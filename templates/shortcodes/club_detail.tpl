<div class="row">
  
  <div class="col-md-4 col-lg-3">
    <div class="teamImage">
      <img src="{{ club.logo }}" class="img-responsive" />
    </div>
  </div>
  <div class="col-md-8 col-lg-9">
    <h3>{{ club.name }}</h3>
    <p>{{ club.description }}</p>
    {% if current_location %}
      <p>
        <strong>Spielort:</strong> {{ current_location.title }}, {{ current_location.description }}</p>
    {% endif %}
  </div>
</div>

<div class="row">

  <div class="col-md-8 col-lg-9 col-md-offset-4 col-lg-offset-3">

    
    <h3>Saison Daten</h3>
    <table class="table table-striped kkl-table">
      <thead>
        <tr>
          <th class="hidden-xs">Mannschaft</th>
          <th>Saison</th>
          <th>Punkte</th>
          <th>Tore</th>
          <th>Position</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        {% for team in teams %}
        <tr>
          <td class="hidden-xs">{{ team.name }}</td>
          <td>{{ team.season.name }}</td>
          <td>{{ team.scores.score }}</td>
          <td>{{ team.scores.goalsFor}}:{{ team.scores.goalsAgainst}}</td>
          <td><strong>{{ team.scores.position}}</strong></td>
          <td><a href="/spielplan/{{ team.schedule_link }}"><small>Spielplan</small></a></td>
        </tr>
        {% endfor %}
      </tbody>
    </table>
  </div>
</div>