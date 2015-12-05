{% if league %}<h5>{{ league.name }}</h5>{% endif %}
<table class="table table-striped kkl-table">
  <thead>
    <tr>
      <th>Datum</th>
      <th>Heim</th>
      <th></th>
      <th>Gast</th>
    </tr>
  </thead>
  <tbody>

  {% for match in schedule %}
  <tr>
    {% if mark == match.homename %} {% endif %}
      <td class="date">
        {% if match.fixture %}
          {{ match.fixture|date('d.m.Y') }}
        {% else %}
          -
        {% endif %}
      </td>

      <td class="home">
      {% if display_result and (match.score_home > match.score_away) %} 
        <b>{{ match.homename }}</b>
      {% else %}
        {{ match.homename }}
      {% endif %}
      </td>

      <td></td>

      <td class="guest">
      {% if display_result and (match.score_home < match.score_away) %} 
        <b>{{ match.awayname }}</b>
      {% else %}
        {{ match.awayname }}
      {% endif %}
      </td>

      {% if display_result and not (match.score_home == 0 and match.score_away == 0)%} - <span class="home">{{ match.score_home }}</span> : <span class="guest">{{ match.score_away }}</span>{% endif %}
    </tr>
  {% endfor %}

  </tbody>
</table>